<?php

namespace App\EventListener;

use App\Entity\AuditLog;
use App\Entity\Budget;
use App\Entity\BudgetLine;
use App\Entity\Category;
use App\Entity\Expense;
use App\Entity\FundMovement;
use App\Entity\LedgerEntry;
use App\Entity\Milestone;
use App\Entity\PettyCashCycle;
use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\Reimbursement;
use App\Entity\Setting;
use App\Entity\Stage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;

/**
 * Records who changed what on planning and money entities. Changes are collected in
 * onFlush (when change sets are known) and written in postFlush (when new ids exist).
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class AuditListener
{
    private const AUDITED = [
        Project::class, ProjectMember::class, Stage::class, Category::class, Budget::class, BudgetLine::class,
        Milestone::class, FundMovement::class, LedgerEntry::class, Expense::class, Reimbursement::class,
        PettyCashCycle::class, Setting::class, User::class,
    ];
    /** Never written to the log. */
    private const HIDDEN_FIELDS = ['password'];

    /** @var list<array{entity: object, action: string, changes: array<string, mixed>, id: ?int}> */
    private array $pending = [];

    public function __construct(private readonly Security $security)
    {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($this->isAudited($entity)) {
                $this->pending[] = ['entity' => $entity, 'action' => 'create', 'changes' => $this->normalize($uow->getEntityChangeSet($entity)), 'id' => null];
            }
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($this->isAudited($entity)) {
                $changes = $this->normalize($uow->getEntityChangeSet($entity));
                if ([] !== $changes) {
                    $this->pending[] = ['entity' => $entity, 'action' => 'update', 'changes' => $changes, 'id' => null];
                }
            }
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($this->isAudited($entity)) {
                // The id is gone after the flush; keep it now.
                $this->pending[] = ['entity' => $entity, 'action' => 'delete', 'changes' => [], 'id' => $this->idOf($entity)];
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

        $connection = $args->getObjectManager()->getConnection();
        $user = $this->security->getUser();
        $userName = $user instanceof User ? $user->getFullName() : null;
        // While an admin views the app as someone else, record both.
        $token = $this->security->getToken();
        if ($token instanceof SwitchUserToken && ($original = $token->getOriginalToken()->getUser()) instanceof User) {
            $userName .= ' (vía '.$original->getFullName().')';
        }
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $table = $args->getObjectManager()->getClassMetadata(AuditLog::class)->getTableName();

        foreach ($pending as $row) {
            $entity = $row['entity'];
            $connection->insert($table, [
                'project_id' => $this->projectIdOf($entity),
                'user_id' => $user instanceof User ? $user->getId() : null,
                'user_name' => $userName,
                'action' => $row['action'],
                'entity_type' => (new \ReflectionClass($entity))->getShortName(),
                'entity_id' => $row['id'] ?? $this->idOf($entity),
                'changes' => json_encode($row['changes'], \JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
            ]);
        }
    }

    private function isAudited(object $entity): bool
    {
        foreach (self::AUDITED as $class) {
            if ($entity instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, array{0: mixed, 1: mixed}> $changeSet
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function normalize(array $changeSet): array
    {
        $changes = [];
        foreach ($changeSet as $field => [$old, $new]) {
            if (\in_array($field, self::HIDDEN_FIELDS, true)) {
                $changes[$field] = ['***', '***'];
                continue;
            }
            $old = $this->scalar($old);
            $new = $this->scalar($new);
            if ($old !== $new) {
                $changes[$field] = [$old, $new];
            }
        }

        return $changes;
    }

    private function scalar(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $value instanceof \BackedEnum => $value->value,
            \is_object($value) => $this->idOf($value),
            // BIGINT columns come back as strings; compare numerically.
            \is_string($value) && preg_match('/^-?\d+$/', $value) => (int) $value,
            default => $value,
        };
    }

    private function idOf(object $entity): ?int
    {
        if ($entity instanceof Setting) {
            return null;
        }

        return method_exists($entity, 'getId') ? $entity->getId() : null;
    }

    private function projectIdOf(object $entity): ?int
    {
        return match (true) {
            $entity instanceof Project => $entity->getId(),
            $entity instanceof Stage, $entity instanceof Category, $entity instanceof Budget, $entity instanceof ProjectMember,
            $entity instanceof FundMovement, $entity instanceof Expense, $entity instanceof PettyCashCycle => $entity->getProject()->getId(),
            $entity instanceof BudgetLine, $entity instanceof Milestone => $entity->getStage()->getProject()->getId(),
            $entity instanceof LedgerEntry => $entity->getMovement()->getProject()->getId(),
            $entity instanceof Reimbursement => $entity->getMovement()->getProject()->getId(),
            default => null,
        };
    }
}
