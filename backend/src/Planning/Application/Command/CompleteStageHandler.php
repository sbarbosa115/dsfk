<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Application\Port\StageFunds;
use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;
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

        $next = null;
        foreach ($this->plans->stagesOf($projectId) as $candidate) {
            if ($candidate->getPosition() > $stage->getPosition() && !$candidate->isCompleted()) {
                $next = $candidate;
                break;
            }
        }
        $this->funds->settle($projectId, (int) $stage->getId(), $next?->getId(), $c->actualEnd, $c->actorId);
    }
}
