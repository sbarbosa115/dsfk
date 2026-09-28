<?php

declare(strict_types=1);

namespace App\Settings\Domain\Model;

use App\Shared\Domain\Model\Audited;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** One stored setting, as JSON. See AppSettings for the keys and defaults. */
#[ORM\Entity]
#[ORM\Table(name: 'setting')]
class Setting implements Audited
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(name: 'setting_key', length: 100)]
        private string $key,
        #[ORM\Column(type: Types::JSON)]
        private mixed $value,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function change(mixed $value): void
    {
        $this->value = $value;
    }

    public function auditProjectId(): ?int
    {
        return null;
    }
}
