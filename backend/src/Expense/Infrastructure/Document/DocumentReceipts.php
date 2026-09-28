<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Document;

use App\Document\Application\Command\AttachFile;
use App\Document\Application\Command\AttachFileHandler;
use App\Document\Application\Command\Upload;
use App\Document\Application\Query\AttachmentQueries;
use App\Expense\Application\Port\Receipts;
use App\Expense\Application\Port\ReceiptStore;

/** Runs Document's handler in the Expense command's transaction (a nested bus dispatch would open another). */
final readonly class DocumentReceipts implements Receipts, ReceiptStore
{
    public function __construct(private AttachmentQueries $attachments, private AttachFileHandler $attach)
    {
    }

    public function has(int $expenseId): bool
    {
        return [] !== ($this->attachments->ofExpenses([$expenseId])[$expenseId] ?? []);
    }

    public function attach(int $projectId, int $expenseId, string $path, string $originalName, int $size, int $actorId): void
    {
        ($this->attach)(AttachFile::toExpense($projectId, $expenseId, new Upload($path, $originalName, $size), $actorId));
    }
}
