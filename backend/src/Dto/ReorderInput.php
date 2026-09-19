<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ReorderInput
{
    /**
     * @param list<int> $ids
     */
    public function __construct(
        #[Assert\NotNull]
        #[Assert\All([new Assert\Type('int')])]
        public ?array $ids = null,
    ) {
    }
}
