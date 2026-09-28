<?php

declare(strict_types=1);

namespace App\Document\Application\Command;

use App\Document\Application\Port\FileStore;
use App\Document\Domain\Model\Attachment;
use App\Document\Domain\Repository\AttachmentRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\NewId;
use Psr\Clock\ClockInterface;

final readonly class AttachFileHandler implements CommandHandler
{
    public function __construct(private AttachmentRepository $attachments, private FileStore $files, private ClockInterface $clock)
    {
    }

    public function __invoke(AttachFile $c): NewId
    {
        $attachment = Attachment::accept(
            $c->projectId,
            $c->file->originalName,
            $this->files->mimeTypeOf($c->file->path),
            $c->file->size,
            bin2hex(random_bytes(16)),
            $c->actorId,
            $this->clock->now(),
        );
        if (null !== $c->movementId) {
            $attachment->attachToMovement($c->movementId);
        }
        if (null !== $c->expenseId) {
            $attachment->attachToExpense($c->expenseId);
        }
        $this->attachments->add($attachment);
        $this->files->keep($c->file->path, $attachment->getStoredName());

        return NewId::of($attachment);
    }
}
