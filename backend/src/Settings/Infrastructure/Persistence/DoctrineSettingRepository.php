<?php

declare(strict_types=1);

namespace App\Settings\Infrastructure\Persistence;

use App\Settings\Application\Query\SettingsQueries;
use App\Settings\Domain\Model\AppSettings;
use App\Settings\Domain\Model\Setting;
use App\Settings\Domain\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineSettingRepository implements SettingRepository, SettingsQueries
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function current(): AppSettings
    {
        $stored = [];
        foreach ($this->em->getRepository(Setting::class)->findAll() as $setting) {
            $stored[$setting->getKey()] = $setting->getValue();
        }

        return AppSettings::fromStored($stored);
    }

    public function save(AppSettings $settings): void
    {
        foreach ($settings->toStored() as $key => $value) {
            $setting = $this->em->find(Setting::class, $key);
            if (null === $setting) {
                $this->em->persist(new Setting($key, $value));
            } elseif ($setting->getValue() !== $value) {
                $setting->change($value);
            }
        }
    }
}
