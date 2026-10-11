<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Application\Port\StageFunds;
use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Domain\Error\InvalidValue;
use Psr\Clock\ClockInterface;

final readonly class CompleteStageHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans, private StageFunds $funds, private ClockInterface $clock)
    {
    }

    public function __invoke(CompleteStage $c): void
    {
        $stage = $this->plans->stage($c->stageId);
        $projectId = $stage->getProjectId();
        $this->plans->budgetFor($projectId)->assertApproved();
        $stage->complete($c->actualEnd, $this->clock->now());

        if (null !== $c->toStageId) {
            $target = null;
            foreach ($this->plans->stagesOf($projectId) as $candidate) {
                if ($candidate->getId() === $c->toStageId) {
                    $target = $candidate;
                }
            }
            // Stages may run at the same time, so the leftover goes where the Admin says, never to a guessed "next" stage.
            if (null === $target || $target === $stage || $target->isCompleted() || $target->isPendingApproval()) {
                throw InvalidValue::field('toStageId', 'Choose another stage that is not completed.');
            }
        }
        $this->funds->settle($projectId, (int) $stage->getId(), $c->toStageId, $c->actualEnd, $c->actorId);
    }
}
