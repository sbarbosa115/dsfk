<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Global application setting stored as JSON. Defaults live in SettingsService.
 */
#[ORM\Entity]
class Setting
{
    #[ORM\Id]
    #[ORM\Column(name: 'setting_key', length: 100)]
    private string $key;

    #[ORM\Column(type: Types::JSON)]
    private mixed $value;

    public function __construct(string $key, mixed $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}
