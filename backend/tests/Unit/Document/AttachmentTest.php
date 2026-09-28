<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document;

use App\Document\Domain\Model\Attachment;
use App\Shared\Domain\Error\InvalidValue;
use PHPUnit\Framework\TestCase;

final class AttachmentTest extends TestCase
{
    public function testAnAcceptedFileGetsARandomNameInsideItsProjectFolder(): void
    {
        $attachment = Attachment::accept(7, '../../etc/comprobante.pdf', 'application/pdf', 2048, str_repeat('ab', 16), 1, new \DateTimeImmutable());

        self::assertSame('7/'.str_repeat('ab', 16).'.pdf', $attachment->getStoredName());
        self::assertSame('.._.._etc_comprobante.pdf', $attachment->getOriginalName(), 'no path separators in the shown name');
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function refused(): iterable
    {
        yield 'a script' => ['text/x-php', 100];
        yield 'an svg (can carry scripts)' => ['image/svg+xml', 100];
        yield 'too big' => ['application/pdf', Attachment::MAX_SIZE + 1];
        yield 'empty' => ['application/pdf', 0];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('refused')]
    public function testFilesOutsideTheRulesAreRefusedOnTheFileField(string $mimeType, int $size): void
    {
        try {
            Attachment::accept(7, 'x', $mimeType, $size, str_repeat('ab', 16), 1, new \DateTimeImmutable());
            self::fail('Expected a refusal');
        } catch (InvalidValue $e) {
            self::assertArrayHasKey('file', $e->extra['violations'] ?? []);
        }
    }
}
