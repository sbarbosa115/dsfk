<?php

namespace App\Controller;

use App\Dto\SettingsInput;
use App\Service\SettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/settings')]
class SettingsController extends AbstractController
{
    /** API field name => storage key. */
    private const FIELDS = [
        'defaultCurrency' => SettingsService::DEFAULT_CURRENCY,
        'teamLeadExpenseLimit' => SettingsService::TEAM_LEAD_EXPENSE_LIMIT,
        'pettyCashLowBalancePercent' => SettingsService::PETTY_CASH_LOW_BALANCE_PERCENT,
        'budgetWarningPercents' => SettingsService::BUDGET_WARNING_PERCENTS,
    ];

    public function __construct(private readonly SettingsService $settings)
    {
    }

    #[Route('', methods: ['GET'])]
    public function show(): JsonResponse
    {
        return $this->json($this->present());
    }

    #[Route('', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(#[MapRequestPayload] SettingsInput $input): JsonResponse
    {
        $values = [];
        foreach (self::FIELDS as $field => $key) {
            if (null !== $input->{$field}) {
                $values[$key] = $input->{$field};
            }
        }
        if (isset($values[SettingsService::BUDGET_WARNING_PERCENTS])) {
            $percents = array_values(array_unique($values[SettingsService::BUDGET_WARNING_PERCENTS]));
            sort($percents);
            $values[SettingsService::BUDGET_WARNING_PERCENTS] = $percents;
        }
        $this->settings->update($values);

        return $this->json($this->present());
    }

    /**
     * @return array<string, mixed>
     */
    private function present(): array
    {
        $all = $this->settings->all();

        return array_map(static fn (string $key) => $all[$key], self::FIELDS);
    }
}
