<?php

namespace App\Controller;

use App\Dto\CompleteStageInput;
use App\Dto\ContingencyDrawInput;
use App\Dto\DepositInput;
use App\Dto\VoidInput;
use App\Entity\Attachment;
use App\Entity\Category;
use App\Entity\FundMovement;
use App\Entity\Project;
use App\Entity\Stage;
use App\Entity\User;
use App\Enum\LedgerAccount;
use App\Enum\MovementType;
use App\Exception\ApiProblem;
use App\Security\ProjectVoter;
use App\Service\AttachmentStorage;
use App\Service\FinancePresenter;
use App\Service\LedgerService;
use App\Service\MoneyConverter;
use App\Service\PettyCashService;
use App\Service\ProjectAccess;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FinanceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LedgerService $ledger,
        private readonly FinancePresenter $presenter,
        private readonly PettyCashService $pettyCash,
    ) {
    }

    #[Route('/api/projects/{id}/finance', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted(ProjectVoter::VIEW_FINANCIALS, 'project')]
    public function summary(Project $project): JsonResponse
    {
        return $this->json($this->presenter->summary($project));
    }

    #[Route('/api/projects/{id}/movements', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted(ProjectVoter::VIEW_FINANCIALS, 'project')]
    public function movements(Project $project): JsonResponse
    {
        $movements = $this->em->getRepository(FundMovement::class)->findBy(
            ['project' => $project, 'type' => [MovementType::Deposit, MovementType::ContingencyDraw, MovementType::Carryover]],
            ['date' => 'DESC', 'id' => 'DESC'],
        );

        return $this->json(array_map($this->presenter->movement(...), $movements));
    }

    #[Route('/api/projects/{id}/deposits', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted(ProjectVoter::ADMINISTER, 'project')]
    public function deposit(Project $project, #[MapRequestPayload] DepositInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->assertApproved($project);

        $movement = new FundMovement($project, MovementType::Deposit, $input->date, $user, $input->note);
        $movement->setPayment($input->method, $input->reference);

        foreach ($input->allocations as $i => $allocation) {
            $path = "allocations[$i]";
            $amount = $this->toMinor($allocation->amount, $project, "$path.amount");

            if (LedgerAccount::Stage === $allocation->destination) {
                $stage = $this->openStage($project, $allocation->stageId, "$path.stageId");
                $category = null;
                if (null !== $allocation->categoryId) {
                    $category = $this->em->find(Category::class, $allocation->categoryId);
                    if (null === $category || $category->getProject() !== $project) {
                        throw ApiProblem::field("$path.categoryId", 'Categoría inválida.');
                    }
                }
                $movement->addEntry(LedgerAccount::Stage, $amount, $stage, $category);
            } else {
                $movement->addEntry($allocation->destination, $amount);
            }
        }

        $this->pettyCash->assign($movement);
        $this->em->persist($movement);
        $this->em->flush();

        return $this->json($this->presenter->movement($movement), 201);
    }

    #[Route('/api/projects/{id}/contingency/draws', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted(ProjectVoter::ADMINISTER, 'project')]
    public function drawContingency(Project $project, #[MapRequestPayload] ContingencyDrawInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->assertApproved($project);
        $stage = $this->openStage($project, $input->stageId, 'stageId');
        $amount = $this->toMinor($input->amount, $project, 'amount');
        if ($amount > $this->ledger->balances($project)->balance(LedgerAccount::Contingency->value)) {
            throw ApiProblem::field('amount', 'El monto supera el saldo disponible de la contingencia.');
        }

        $movement = new FundMovement($project, MovementType::ContingencyDraw, $input->date, $user, $input->reason);
        $movement->addEntry(LedgerAccount::Contingency, -$amount);
        $movement->addEntry(LedgerAccount::Stage, $amount, $stage);
        $this->em->persist($movement);
        $this->em->flush();

        return $this->json($this->presenter->movement($movement), 201);
    }

    #[Route('/api/movements/{id}/void', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function void(FundMovement $movement, #[MapRequestPayload] VoidInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProjectVoter::ADMINISTER, $movement->getProject());
        // Carry-overs follow from closing a stage; expenses and reimbursements are voided through their expense.
        if (!\in_array($movement->getType(), [MovementType::Deposit, MovementType::ContingencyDraw], true)) {
            throw new \DomainException('movement_not_voidable');
        }
        $this->pettyCash->assertMutable($movement);
        $this->ledger->void($movement, $user, $input->reason);
        $this->em->flush();

        return $this->json($this->presenter->movement($movement));
    }

    #[Route('/api/movements/{id}/attachments', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function attach(FundMovement $movement, Request $request, AttachmentStorage $storage, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProjectVoter::ADMINISTER, $movement->getProject());
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            throw ApiProblem::field('file', 'Selecciona un archivo.');
        }

        $attachment = $storage->store($file, $movement->getProject(), $user);
        $attachment->attachTo($movement);
        $movement->getAttachments()->add($attachment);
        $this->em->persist($attachment);
        $this->em->flush();

        return $this->json($this->presenter->movement($movement), 201);
    }

    #[Route('/api/attachments/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function download(Attachment $attachment, AttachmentStorage $storage, ProjectAccess $access, #[CurrentUser] User $user): BinaryFileResponse
    {
        $expense = $attachment->getExpense();
        if (null !== $expense && $expense->getPaidBy()->getId() === $user->getId()) {
            // Team Leads can open the receipts of their own expenses.
        } elseif (!$access->isManager($attachment->getProject(), $user)) {
            throw $this->createAccessDeniedException();
        }
        $path = $storage->path($attachment);
        if (!is_file($path)) {
            throw new NotFoundHttpException('file_missing');
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $attachment->getMimeType());
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $attachment->getOriginalName(), 'archivo');

        return $response;
    }

    #[Route('/api/stages/{id}/complete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function completeStage(Stage $stage, #[MapRequestPayload] CompleteStageInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $project = $stage->getProject();
        $this->denyAccessUnlessGranted(ProjectVoter::ADMINISTER, $project);
        $this->assertApproved($project);

        $this->ledger->completeStage($stage, $input->actualEnd, $user);
        $this->em->flush();

        return $this->json($this->presenter->summary($project));
    }

    private function assertApproved(Project $project): void
    {
        if (!$project->getBudget()->isApproved()) {
            throw new \DomainException('budget_not_approved');
        }
    }

    private function openStage(Project $project, ?int $stageId, string $field): Stage
    {
        $stage = null === $stageId ? null : $this->em->find(Stage::class, $stageId);
        if (null === $stage || $stage->getProject() !== $project) {
            throw ApiProblem::field($field, 'Etapa inválida.');
        }
        if ($stage->isCompleted()) {
            throw ApiProblem::field($field, 'La etapa ya está finalizada.');
        }

        return $stage;
    }

    private function toMinor(string $amount, Project $project, string $field): int
    {
        try {
            return MoneyConverter::toMinor($amount, $project->getCurrency());
        } catch (\InvalidArgumentException) {
            throw ApiProblem::field($field, 'Monto inválido para la moneda del proyecto.');
        }
    }
}
