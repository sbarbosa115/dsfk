<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class ReorderInput
{
    /**
     * @param list<int> $ids every stage of the project, in the new order
     */
    public function __construct(
        #[Assert\All([new Assert\Type('int')])]
        public array $ids = [],
    ) {
    }
}
