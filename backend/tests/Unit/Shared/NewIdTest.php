<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Application\Bus\NewId;
use PHPUnit\Framework\TestCase;

final class NewIdTest extends TestCase
{
    public function testTheIdIsReadWhenAskedForSoItReflectsTheCommit(): void
    {
        $record = new class {
            public ?int $id = null;

            public function getId(): ?int
            {
                return $this->id;
            }
        };
        $newId = NewId::of($record);

        $record->id = 42;

        self::assertSame(42, $newId->value());
    }

    public function testReadingItBeforeTheCommitIsAProgrammingError(): void
    {
        $this->expectException(\LogicException::class);

        NewId::of(new class {
            public function getId(): ?int
            {
                return null;
            }
        })->value();
    }
}
