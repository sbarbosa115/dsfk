<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Controller;

use App\Expense\Application\Command\AddReceipt;
use App\Expense\Application\Command\ApproveExpense;
use App\Expense\Application\Command\CorrectExpense;
use App\Expense\Application\Command\ExpenseDetails;
use App\Expense\Application\Command\RecordExpense;
use App\Expense\Application\Command\ReimburseExpenses;
use App\Expense\Application\Command\RejectExpense;
use App\Expense\Application\Command\VoidExpense;
use App\Expense\Application\Query\ExpenseQueries;
use App\Expense\Domain\Model\ExpenseStatus;
use App\Expense\Domain\Model\PayoutMethod;
use App\Expense\Domain\Repository\ExpenseRepository;
use App\Expense\UI\Http\Input\ExpenseInput;
use App\Expense\UI\Http\Input\ReasonInput;
use App\Expense\UI\Http\Input\ReimbursementInput;
use App\Expense\UI\Http\Output\ExpenseOutput;
use App\Expense\UI\Http\Output\ExpensePageOutput;
use App\Expense\UI\Http\Presenter\ExpensePresenter;
use App\Expense\UI\Http\Presenter\Viewer;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\NewId;
use App\Shared\Application\Security\Actor;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Error\NotFound;
use App\Shared\UI\Http\ProjectGuard;
use App\Shared\UI\Http\ProjectPermission;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag(name: 'Expenses')]
#[OA\Response(response: 403, description: 'forbidden', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 404, description: 'expense_not_found (also another Team Lead\'s), or project_not_found outside the project', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class ExpenseController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $bus,
        private readonly ProjectGuard $guard,
        private readonly ExpensePresenter $presenter,
        private readonly ExpenseQueries $queries,
        private readonly ExpenseRepository $expenses,
    ) {
    }

    /** The PM and Admins see every expense; a Team Lead their own. With the Team Lead expenses in numbers. */
    #[Route('/api/projects/{id}/expenses', name: 'api_expenses_list', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(response: 200, description: 'One page of expenses, newest first', content: new Model(type: ExpensePageOutput::class))]
    public function list(
        int $id,
        #[CurrentUser] Actor $actor,
        #[MapQueryParameter] ?string $q = null,
        #[MapQueryParameter] ?string $status = null,
        #[MapQueryParameter] ?int $stageId = null,
        #[MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[MapQueryParameter(options: ['min_range' => 1, 'max_range' => 100])] int $perPage = 50,
    ): JsonResponse {
        $this->guard->require(ProjectPermission::VIEW, $id);
        $viewer = $this->viewer($id, $actor);
        $statuses = [];
        foreach (null === $status || '' === $status ? [] : explode(',', $status) as $value) {
            $statuses[] = ExpenseStatus::tryFrom($value) ?? throw InvalidValue::field('status', 'Invalid status.');
        }
        $result = $this->queries->page($id, $viewer->manager ? null : $actor->getId(), $q, $statuses, $stageId, $page, $perPage);

        return $this->json(new ExpensePageOutput($this->presenter->many($id, $result->items, $viewer), $result->total, $page, $perPage, $this->presenter->summary($id, $viewer)));
    }

    #[Route('/api/projects/{id}/expenses', name: 'api_expenses_record', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[OA\Response(response: 201, description: 'The expense with its history', content: new Model(type: ExpenseOutput::class))]
    #[OA\Response(response: 409, description: 'budget_not_approved', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 422, description: 'validation_failed; insufficient_funds (with `available`)', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function record(int $id, #[MapRequestPayload] ExpenseInput $input, #[CurrentUser] Actor $actor): JsonResponse
    {
        $this->guard->require(ProjectPermission::VIEW, $id);
        $viewer = $this->viewer($id, $actor);
        $expense = $this->bus->dispatch(new RecordExpense($id, $actor->getId(), $viewer->manager, self::details($input), $input->paidFrom));
        \assert($expense instanceof NewId);

        return $this->json($this->presenter->one($this->expenses->get($expense->value()), $viewer), 201);
    }

    #[Route('/api/expenses/{id}', name: 'api_expenses_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(response: 200, description: 'The expense with its history', content: new Model(type: ExpenseOutput::class))]
    public function show(int $id, #[CurrentUser] Actor $actor): JsonResponse
    {
        return $this->answer($id, $this->visible($id, $actor));
    }

    /** Its Team Lead corrects a pending or rejected expense; it goes back to the PM. */
    #[Route('/api/expenses/{id}', name: 'api_expenses_correct', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[OA\Response(response: 200, description: 'The expense', content: new Model(type: ExpenseOutput::class))]
    #[OA\Response(response: 409, description: 'expense_not_editable', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function correct(int $id, #[MapRequestPayload] ExpenseInput $input, #[CurrentUser] Actor $actor): JsonResponse
    {
        $viewer = $this->visible($id, $actor);
        $this->bus->dispatch(new CorrectExpense($id, $actor->getId(), self::details($input)));

        return $this->answer($id, $viewer);
    }

    #[Route('/api/expenses/{id}/approve', name: 'api_expenses_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[OA\Response(response: 200, description: 'The expense: APPROVED, or PM_APPROVED above the Team Lead limit', content: new Model(type: ExpenseOutput::class))]
    #[OA\Response(response: 409, description: 'receipt_required, expense_awaiting_admin, expense_invalid_status', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function approve(int $id, #[CurrentUser] Actor $actor): JsonResponse
    {
        $viewer = $this->manager($id, $actor);
        $this->bus->dispatch(new ApproveExpense($id, $actor->getId(), $viewer->admin));

        return $this->answer($id, $viewer);
    }

    #[Route('/api/expenses/{id}/reject', name: 'api_expenses_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[OA\Response(response: 200, description: 'The expense', content: new Model(type: ExpenseOutput::class))]
    #[OA\Response(response: 409, description: 'expense_awaiting_admin, expense_invalid_status', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function reject(int $id, #[MapRequestPayload] ReasonInput $input, #[CurrentUser] Actor $actor): JsonResponse
    {
        $viewer = $this->manager($id, $actor);
        $this->bus->dispatch(new RejectExpense($id, $actor->getId(), $viewer->admin, $input->reason));

        return $this->answer($id, $viewer);
    }

    /** An Admin voids an approved expense (not one paid back); its money goes back to the stage or petty cash. */
    #[Route('/api/expenses/{id}/void', name: 'api_expenses_void', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[OA\Response(response: 200, description: 'The expense', content: new Model(type: ExpenseOutput::class))]
    #[OA\Response(response: 409, description: 'expense_invalid_status, cycle_closed', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function void(int $id, #[MapRequestPayload] ReasonInput $input, #[CurrentUser] Actor $actor): JsonResponse
    {
        $project = $this->project($id);
        $this->guard->require(ProjectPermission::ADMINISTER, $project);
        $this->bus->dispatch(new VoidExpense($id, $actor->getId(), $input->reason));

        return $this->answer($id, $this->viewer($project, $actor));
    }

    /** A receipt (multipart field "file"): its owner while it can be corrected, the PM or an Admin unless voided. */
    #[Route('/api/expenses/{id}/attachments', name: 'api_expenses_attach', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[OA\RequestBody(content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(required: ['file'], properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])))]
    #[OA\Response(response: 201, description: 'The expense with its receipts', content: new Model(type: ExpenseOutput::class))]
    #[OA\Response(response: 422, description: 'validation_failed on "file"', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function attach(int $id, Request $request, #[CurrentUser] Actor $actor): JsonResponse
    {
        $viewer = $this->visible($id, $actor);
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            throw InvalidValue::field('file', 'Select a file.');
        }
        if (!$file->isValid()) {
            throw InvalidValue::field('file', \UPLOAD_ERR_INI_SIZE === $file->getError() || \UPLOAD_ERR_FORM_SIZE === $file->getError() ? 'The file is larger than 10 MB.' : 'The file could not be uploaded.');
        }
        $this->bus->dispatch(new AddReceipt($id, $actor->getId(), $viewer->manager, $file->getPathname(), $file->getClientOriginalName(), (int) $file->getSize()));

        return $this->answer($id, $viewer, 201);
    }

    /** The PM or an Admin pays approved Team Lead expenses back from petty cash. */
    #[Route('/api/projects/{id}/reimbursements', name: 'api_expenses_reimburse', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[OA\Response(response: 201, description: 'The expenses, now REIMBURSED', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: ExpenseOutput::class))))]
    #[OA\Response(response: 422, description: 'validation_failed on expenseIds; insufficient_funds', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function reimburse(int $id, #[MapRequestPayload] ReimbursementInput $input, #[CurrentUser] Actor $actor): JsonResponse
    {
        $this->guard->require(ProjectPermission::PLAN, $id);
        $this->bus->dispatch(new ReimburseExpenses($id, $actor->getId(), $input->expenseIds, new \DateTimeImmutable($input->date), $input->method ?? PayoutMethod::Other, $input->reference));

        return $this->json($this->presenter->many($id, $this->expenses->many($input->expenseIds), $this->viewer($id, $actor)), 201);
    }

    private function answer(int $expenseId, Viewer $viewer, int $status = 200): JsonResponse
    {
        return $this->json($this->presenter->one($this->expenses->get($expenseId), $viewer), $status);
    }

    private function project(int $expenseId): int
    {
        return $this->queries->projectOf($expenseId) ?? throw new NotFound('expense_not_found');
    }

    /** Its Team Lead, or the project's PM and Admins; another Team Lead does not learn it exists. */
    private function visible(int $expenseId, Actor $actor): Viewer
    {
        $project = $this->project($expenseId);
        $this->guard->require(ProjectPermission::VIEW, $project);
        $viewer = $this->viewer($project, $actor);
        if (!$viewer->manager && $this->expenses->get($expenseId)->getPaidById() !== $actor->getId()) {
            throw new NotFound('expense_not_found');
        }

        return $viewer;
    }

    private function manager(int $expenseId, Actor $actor): Viewer
    {
        $project = $this->project($expenseId);
        $this->guard->require(ProjectPermission::PLAN, $project);

        return $this->viewer($project, $actor);
    }

    private function viewer(int $projectId, Actor $actor): Viewer
    {
        return new Viewer(
            $actor->getId(),
            $this->guard->allows(ProjectPermission::PLAN, $projectId),
            $this->guard->allows(ProjectPermission::ADMINISTER, $projectId),
        );
    }

    private static function details(ExpenseInput $input): ExpenseDetails
    {
        return new ExpenseDetails((int) $input->stageId, (int) $input->categoryId, new \DateTimeImmutable($input->date), $input->amount, $input->description, $input->supplier, $input->invoiceNumber);
    }
}
