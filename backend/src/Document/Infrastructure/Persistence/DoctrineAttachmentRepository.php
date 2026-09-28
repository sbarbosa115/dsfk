<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Persistence;

use App\Document\Application\Query\AttachmentQueries;
use App\Document\Application\Query\AttachmentView;
use App\Document\Domain\Model\Attachment;
use App\Document\Domain\Repository\AttachmentRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineAttachmentRepository implements AttachmentRepository, AttachmentQueries
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function add(Attachment $attachment): void
    {
        $this->em->persist($attachment);
    }

    public function find(int $id): ?AttachmentView
    {
        $attachment = $this->em->find(Attachment::class, $id);

        return null === $attachment ? null : self::view($attachment);
    }

    public function ofMovements(array $movementIds): array
    {
        if ([] === $movementIds) {
            return [];
        }
        $byMovement = [];
        foreach ($this->em->getRepository(Attachment::class)->findBy(['movementId' => $movementIds], ['id' => 'ASC']) as $attachment) {
            $byMovement[(int) $attachment->getMovementId()][] = self::view($attachment);
        }

        return $byMovement;
    }

    private static function view(Attachment $a): AttachmentView
    {
        return new AttachmentView((int) $a->getId(), $a->getProjectId(), $a->getMovementId(), $a->getExpenseId(), $a->getOriginalName(), $a->getMimeType(), $a->getSize(), $a->getStoredName());
    }
}
