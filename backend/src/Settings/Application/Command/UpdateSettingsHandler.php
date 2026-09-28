<?php

declare(strict_types=1);

namespace App\Settings\Application\Command;

use App\Settings\Domain\Repository\SettingRepository;
use App\Shared\Application\Bus\CommandHandler;

final readonly class UpdateSettingsHandler implements CommandHandler
{
    public function __construct(private SettingRepository $settings)
    {
    }

    public function __invoke(UpdateSettings $command): void
    {
        $this->settings->save($this->settings->current()->with(
            $command->defaultCurrency,
            $command->teamLeadExpenseLimit,
            $command->pettyCashLowBalancePercent,
            $command->budgetWarningPercents,
        ));
    }
}
