<?php

namespace App\Controller;

use App\Dto\ExpenseInput;
use App\Dto\ReasonInput;
use App\Dto\ReimbursementInput;
use App\Entity\Category;
use App\Entity\Expense;
use App\Entity\Project;
use App\Entity\Stage;
use App\Entity\User;
use App\Enum\ExpenseStatus;
use App\Enum\PaidFrom;
use App\Exception\ApiProblem;
use App\Security\ProjectVoter;
use App\Service\AttachmentStorage;
use App\Service\ExpensePresenter;
use App\Service\ExpenseService;
use App\Service\MoneyConverter;
use App\Service\ProjectAccess;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ExpenseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExpenseService $expenses,
        private readonly ExpensePresenter $presenter,
        private readonly ProjectAccess $access,
    ) {
    }

    /** Managers see every expense; Team Leads only their own. */
    #[Route('/api/projects/{id}/expenses', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted(ProjectVoter::VIEW, 'project')]
    public function list(Project $project, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $isManager = $this->access->isManager($project, $user);
        $qb = $this->em->createQueryBuilder()
            ->select('e')->from(Expense::class, 'e')
            ->where('e.project = :project')->setParameter('project', $project)
            ->orderBy('e.date', 'DESC')->addOrderBy('e.id', 'DESC');
        if (!$isManager) {
            $qb->andWhere('e.paidBy = :user')->setParameter('user', $user);
        }
        if ($status = $request->query->get('status')) {
            $qb->andWhere('e.status IN (:statuses)')->setParameter('statuses', explode(',', $status));
        }
        if ($stageId = $request->query->getInt('stageId')) {
            $qb->andWhere('e.stage = :stage')->setParameter('stage', $stageId);
        }
        /** @var list<Expense> $items */
        $items = $qb->getQuery()->getResult();

        return $this->json([
            'items' => array_map(fn (Expense $e) => $this->presenter->present($e), $items),
            'summary' => $this->summary($project, $user, $isManager),
        ]);
    }

    #[Route('/api/projects/{id}/expenses', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted(ProjectVoter::VIEW, 'project')]
    public function create(Project $project, #[MapRequestPayload] ExpenseInput $input, #[CurrentUser] User $user): JsonResponse
    {
        if (!$project->getBudget()->isApproved()) {
            throw new \DomainException('budget_not_approved');
        }
        if (null === $input->paidFrom) {
            throw ApiProblem::field('paidFrom', 'Este valor no debería estar vacío.');
        }

        $expense = new Expense($project, $input->paidFrom, $user);
        $this->applyDetails($expense, $input);
        $this->expenses->create($expense, $user);
        $this->em->flush();

        return $this->json($this->presenter->present($expense, true), 201);
    }

    #[Route('/api/expenses/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Expense $expense, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyUnlessCanSee($expense, $user);

        return $this->json($this->presenter->present($expense, true));
    }

    /** The Team Lead corrects a pending or rejected expense; it goes back to SUBMITTED. */
    #[Route('/api/expenses/{id}', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Expense $expense, #[MapRequestPayload] ExpenseInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $previous = $expense->snapshot();
        $this->applyDetails($expense, $input);
        $this->expenses->update($expense, $user, $previous);
        $this->em->flush();

        return $this->json($this->presenter->present($expense, true));
    }

    #[Route('/api/expenses/{id}/approve', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function approve(Expense $expense, #[CurrentUser] User $user): JsonResponse
    {
        $this->expenses->approve($expense, $user);
        $this->em->flush();

        return $this->json($this->presenter->present($expense, true));
    }

    #[Route('/api/expenses/{id}/reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reject(Expense $expense, #[MapRequestPayload] ReasonInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->expenses->reject($expense, $user, $input->reason);
        $this->em->flush();

        return $this->json($this->presenter->present($expense, true));
    }

    #[Route('/api/expenses/{id}/void', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function void(Expense $expense, #[MapRequestPayload] ReasonInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProjectVoter::ADMINISTER, $expense->getProject());
        $this->expenses->void($expense, $user, $input->reason);
        $this->em->flush();

        return $this->json($this->presenter->present($expense, true));
    }

    #[Route('/api/expenses/{id}/attachments', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function attach(Expense $expense, Request $request, AttachmentStorage $storage, #[CurrentUser] User $user): JsonResponse
    {
        $isOwner = $expense->getPaidBy()->getId() === $user->getId();
        $allowed = ($isOwner && $expense->isEditableByOwner())
            || ($this->access->isManager($expense->getProject(), $user) && ExpenseStatus::Voided !== $expense->getStatus());
        if (!$allowed) {
            throw new AccessDeniedHttpException();
        }
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            throw ApiProblem::field('file', 'Selecciona un archivo.');
        }

        $attachment = $storage->store($file, $expense->getProject(), $user);
        $attachment->attachToExpense($expense);
        $expense->getAttachments()->add($attachment);
        $this->em->persist($attachment);
        $this->em->flush();

        return $this->json($this->presenter->present($expense, true), 201);
    }

    #[Route('/api/projects/{id}/reimbursements', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reimburse(Project $project, #[MapRequestPayload] ReimbursementInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $items = $this->em->getRepository(Expense::class)->findBy(['id' => $input->expenseIds]);
        if (\count($items) !== \count(array_unique($input->expenseIds))) {
            throw ApiProblem::field('expenseIds', 'Gasto no encontrado.');
        }
        $this->expenses->reimburse($project, $items, $input->date, $input->method, $input->reference, $user);
        $this->em->flush();

        return $this->json(array_map(fn (Expense $e) => $this->presenter->present($e), $items), 201);
    }

    private function applyDetails(Expense $expense, ExpenseInput $input): void
    {
        $project = $expense->getProject();
        $stage = $this->em->find(Stage::class, $input->stageId);
        if (null === $stage || $stage->getProject() !== $project) {
            throw ApiProblem::field('stageId', 'Etapa inválida.');
        }
        $category = $this->em->find(Category::class, $input->categoryId);
        if (null === $category || $category->getProject() !== $project) {
            throw ApiProblem::field('categoryId', 'Categoría inválida.');
        }
        try {
            $amount = MoneyConverter::toMinor($input->amount, $project->getCurrency());
        } catch (\InvalidArgumentException) {
            throw ApiProblem::field('amount', 'Monto inválido para la moneda del proyecto.');
        }

        $expense->setDetails($stage, $category, $input->date, $amount, $input->description, $input->supplier, $input->invoiceNumber);
    }

    private function denyUnlessCanSee(Expense $expense, User $user): void
    {
        if ($expense->getPaidBy()->getId() !== $user->getId() && !$this->access->isManager($expense->getProject(), $user)) {
            throw new AccessDeniedHttpException();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Project $project, User $user, bool $isManager): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('e.status AS status', 'COUNT(e.id) AS n', 'SUM(e.amount) AS total')
            ->from(Expense::class, 'e')
            ->where('e.project = :project')->setParameter('project', $project)
            ->andWhere('e.paidFrom = :oop')->setParameter('oop', PaidFrom::OutOfPocket)
            ->groupBy('e.status');
        if (!$isManager) {
            $qb->andWhere('e.paidBy = :user')->setParameter('user', $user);
        }

        $by = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $status = $row['status'] instanceof ExpenseStatus ? $row['status']->value : $row['status'];
            $by[$status] = ['count' => (int) $row['n'], 'total' => (int) $row['total']];
        }
        $money = static fn (int $minor) => MoneyConverter::toMajor($minor, $project->getCurrency());
        $sum = static fn (string ...$statuses) => array_sum(array_map(static fn ($s) => $by[$s]['total'] ?? 0, $statuses));
        $count = static fn (string ...$statuses) => array_sum(array_map(static fn ($s) => $by[$s]['count'] ?? 0, $statuses));

        return [
            // Waiting for a decision (PM, or Admin above the limit).
            'pendingCount' => $count('SUBMITTED', 'PM_APPROVED'),
            'pendingTotal' => $money($sum('SUBMITTED', 'PM_APPROVED')),
            // Approved, not yet paid back: what the project owes its Team Leads.
            'toReimburseCount' => $count('APPROVED'),
            'toReimburseTotal' => $money($sum('APPROVED')),
            'teamLeadLimit' => $money($this->expenses->teamLeadLimit($project)),
        ];
    }
}
