<?php

namespace App\Service;

use App\Entity\Attachment;
use App\Entity\Expense;
use App\Entity\ExpenseEvent;
use App\Entity\User;
use App\Enum\ExpenseStatus;
use App\Enum\PaidFrom;
use Symfony\Bundle\SecurityBundle\Security;

class ExpensePresenter
{
    public function __construct(
        private readonly Security $security,
        private readonly ProjectAccess $access,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Expense $e, bool $withHistory = false): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $project = $e->getProject();
        $currency = $project->getCurrency();
        $role = $this->access->role($project, $user);
        $isAdmin = ProjectAccess::ADMIN === $role;
        $isManager = $this->access->isManager($project, $user);
        $isOwner = $e->getPaidBy()->getId() === $user->getId();
        $status = $e->getStatus();
        $pending = ExpenseStatus::Submitted === $status || (ExpenseStatus::PmApproved === $status && $isAdmin);

        $data = [
            'id' => $e->getId(),
            'date' => $e->getDate()->format('Y-m-d'),
            'amount' => MoneyConverter::toMajor($e->getAmount(), $currency),
            'description' => $e->getDescription(),
            'supplier' => $e->getSupplier(),
            'invoiceNumber' => $e->getInvoiceNumber(),
            'stage' => ['id' => $e->getStage()->getId(), 'name' => $e->getStage()->getName()],
            'category' => ['id' => $e->getCategory()->getId(), 'name' => $e->getCategory()->getName()],
            'paidFrom' => $e->getPaidFrom()->value,
            'paidBy' => ['id' => $e->getPaidBy()->getId(), 'fullName' => $e->getPaidBy()->getFullName()],
            'status' => $status->value,
            'rejectionReason' => $e->getRejectionReason(),
            'reimbursement' => $e->getReimbursement() ? [
                'id' => $e->getReimbursement()->getId(),
                'date' => $e->getReimbursement()->getMovement()->getDate()->format('Y-m-d'),
                'method' => $e->getReimbursement()->getMethod()->value,
            ] : null,
            'createdAt' => $e->getCreatedAt()->format(\DATE_ATOM),
            'attachments' => array_map(static fn (Attachment $a) => [
                'id' => $a->getId(),
                'name' => $a->getOriginalName(),
                'mimeType' => $a->getMimeType(),
                'size' => $a->getSize(),
            ], $e->getAttachments()->toArray()),
            'permissions' => [
                'edit' => $isOwner && $e->isEditableByOwner(),
                'attach' => ($isOwner && $e->isEditableByOwner()) || ($isManager && ExpenseStatus::Voided !== $status),
                'approve' => $isManager && $pending,
                'reject' => $isManager && $pending,
                'void' => $isAdmin && ExpenseStatus::Approved === $status && null === $e->getReimbursement(),
                'reimburse' => $isManager && ExpenseStatus::Approved === $status && PaidFrom::OutOfPocket === $e->getPaidFrom(),
            ],
        ];

        if ($withHistory) {
            $data['events'] = array_map(static fn (ExpenseEvent $ev) => [
                'type' => $ev->getType(),
                'user' => $ev->getUser()->getFullName(),
                'comment' => $ev->getComment(),
                'previous' => null === $ev->getPrevious() ? null : [
                    ...$ev->getPrevious(),
                    'amount' => MoneyConverter::toMajor((int) $ev->getPrevious()['amount'], $currency),
                ],
                'createdAt' => $ev->getCreatedAt()->format(\DATE_ATOM),
            ], $e->getEvents()->toArray());
        }

        return $data;
    }
}
