<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Doctrine;

use App\Shared\Application\Security\Actor;
use App\Shared\Domain\Model\Audited;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;

/**
 * Records who changed what on every `Audited` entity. Changes are collected in onFlush (when the change sets are
 * known) and written in postFlush (when new ids exist), in the same transaction as the change itself.
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class AuditTrail
{
    /** Never written: a value that must not leave the user table. */
    private const HIDDEN = ['password'];

    /** @var list<array{entity: Audited, action: string, changes: array<string, mixed>, id: ?int}> */
    private array $pending = [];

    public function __construct(private readonly Security $security, private readonly ClockInterface $clock)
    {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Audited) {
                $this->pending[] = ['entity' => $entity, 'action' => 'create', 'changes' => $this->changes($uow->getEntityChangeSet($entity)), 'id' => null];
            }
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Audited) {
                $changes = $this->changes($uow->getEntityChangeSet($entity));
                if ([] !== $changes) {
                    $this->pending[] = ['entity' => $entity, 'action' => 'update', 'changes' => $changes, 'id' => null];
                }
            }
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof Audited) {
                // The id is gone after the flush: keep it now.
                $this->pending[] = ['entity' => $entity, 'action' => 'delete', 'changes' => [], 'id' => self::idOf($entity)];
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ([] === $this->pending) {
            return;
        }
        $pending = $this->pending;
        $this->pending = [];
        [$userId, $userName] = $this->who();
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $connection = $args->getObjectManager()->getConnection();

        foreach ($pending as $row) {
            $connection->insert('audit_log', [
                'project_id' => $row['entity']->auditProjectId(),
                'user_id' => $userId,
                'user_name' => $userName,
                'action' => $row['action'],
                'entity_type' => (new \ReflectionClass($row['entity']))->getShortName(),
                'entity_id' => $row['id'] ?? self::idOf($row['entity']),
                'changes' => json_encode($row['changes'], \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR),
                'created_at' => $now,
            ]);
        }
    }

    /**
     * The signed-in person; while an Admin views the app as someone else, both: "Laura Gómez (vía Ana Admin)".
     *
     * @return array{?int, ?string}
     */
    private function who(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof Actor) {
            return [null, null];
        }
        $name = $user->getFullName();
        $token = $this->security->getToken();
        if ($token instanceof SwitchUserToken && ($original = $token->getOriginalToken()->getUser()) instanceof Actor) {
            $name .= ' (vía '.$original->getFullName().')';
        }

        return [$user->getId(), mb_substr($name, 0, 150)];
    }

    /**
     * @param array<string, mixed> $changeSet field => [old, new] (collections are not audited)
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function changes(array $changeSet): array
    {
        $changes = [];
        foreach ($changeSet as $field => $pair) {
            if (!\is_array($pair) || !\array_key_exists(0, $pair) || !\array_key_exists(1, $pair)) {
                continue;
            }
            [$old, $new] = $pair;
            if (\in_array($field, self::HIDDEN, true)) {
                $changes[$field] = ['***', '***'];
                continue;
            }
            $old = self::scalar($old);
            $new = self::scalar($new);
            if ($old !== $new) {
                $changes[$field] = [$old, $new];
            }
        }

        return $changes;
    }

    private static function scalar(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $value instanceof \BackedEnum => $value->value,
            \is_object($value) => self::idOf($value),
            // BIGINT columns come back as strings: compare numerically.
            \is_string($value) && 1 === preg_match('/^-?\d+$/', $value) => (int) $value,
            default => $value,
        };
    }

    private static function idOf(object $entity): ?int
    {
        if (!method_exists($entity, 'getId')) {
            return null;
        }
        $id = $entity->getId();

        return \is_int($id) ? $id : null;
    }
}
