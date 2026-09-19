<?php

namespace App\Service;

use App\Entity\Setting;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Global settings with code-level defaults. Only keys listed in DEFAULTS exist.
 */
class SettingsService
{
    public const DEFAULT_CURRENCY = 'default_currency';
    /** Major units in the project currency; above it an Admin must approve too. */
    public const TEAM_LEAD_EXPENSE_LIMIT = 'team_lead_expense_limit';
    public const PETTY_CASH_LOW_BALANCE_PERCENT = 'petty_cash_low_balance_percent';
    public const BUDGET_WARNING_PERCENTS = 'budget_warning_percents';

    public const DEFAULTS = [
        self::DEFAULT_CURRENCY => 'COP',
        self::TEAM_LEAD_EXPENSE_LIMIT => '500000',
        self::PETTY_CASH_LOW_BALANCE_PERCENT => 20,
        self::BUDGET_WARNING_PERCENTS => [80, 100],
    ];

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $values = self::DEFAULTS;
        foreach ($this->em->getRepository(Setting::class)->findAll() as $setting) {
            if (\array_key_exists($setting->getKey(), $values)) {
                $values[$setting->getKey()] = $setting->getValue();
            }
        }

        return $values;
    }

    public function get(string $key): mixed
    {
        if (!\array_key_exists($key, self::DEFAULTS)) {
            throw new \InvalidArgumentException(\sprintf('Unknown setting "%s".', $key));
        }

        return $this->em->find(Setting::class, $key)?->getValue() ?? self::DEFAULTS[$key];
    }

    /**
     * @param array<string, mixed> $values
     */
    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            if (!\array_key_exists($key, self::DEFAULTS)) {
                throw new \InvalidArgumentException(\sprintf('Unknown setting "%s".', $key));
            }
            $setting = $this->em->find(Setting::class, $key);
            if (null === $setting) {
                $this->em->persist(new Setting($key, $value));
            } else {
                $setting->setValue($value);
            }
        }
        $this->em->flush();
    }
}
