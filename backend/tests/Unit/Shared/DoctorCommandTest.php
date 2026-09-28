<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\UI\Cli\DoctorCommand;
use PHPUnit\Framework\TestCase;

final class DoctorCommandTest extends TestCase
{
    public function testTheMailerDsnIsShownWithoutItsCredentials(): void
    {
        self::assertSame('smtp://***@mail.example.com:465', DoctorCommand::maskDsn('smtp://user:secret@mail.example.com:465'));
        // cPanel passwords may contain "@": nothing of them is left.
        self::assertSame('smtp://***@mail.example.com:465', DoctorCommand::maskDsn('smtp://obra@example.com:p@ss@mail.example.com:465'));
        self::assertSame('null://null', DoctorCommand::maskDsn('null://null'));
    }
}
