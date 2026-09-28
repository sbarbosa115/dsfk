<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Input;

use App\Shared\UI\Http\Patterns;
use Symfony\Component\Validator\Constraints as Assert;

final class LineInput
{
    public function __construct(
        #[Assert\Positive]
        public int $categoryId = 0,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $description = '',
        /** m², m³, kg, día, global… */
        #[Assert\NotBlank]
        #[Assert\Length(max: 20)]
        public string $unit = '',
        /** Up to 3 decimals. */
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: Patterns::QUANTITY, message: 'Cantidad inválida (máximo 3 decimales).')]
        #[Assert\Positive]
        public string $quantity = '',
        /** Major units, e.g. "35000.50". */
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: Patterns::AMOUNT, message: 'Debe ser un monto positivo.')]
        public string $unitPrice = '',
    ) {
    }
}
