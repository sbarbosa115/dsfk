<?php

declare(strict_types=1);

namespace App\Planning\Application\Command;

use App\Planning\Application\Port\ProjectCatalog;
use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Money\MinorUnits;

final readonly class SetContingencyHandler implements CommandHandler
{
    public function __construct(private PlanRepository $plans, private ProjectCatalog $projects)
    {
    }

    public function __invoke(SetContingency $command): void
    {
        $budget = $this->plans->budgetFor($command->projectId);
        $budget->assertEditable();
        try {
            $minor = MinorUnits::fromMajor($command->amount, $this->projects->currency($command->projectId));
        } catch (InvalidValue) {
            throw InvalidValue::field('contingency', 'Invalid amount for the project\'s currency.');
        }
        $budget->changeContingency($minor);
    }
}
