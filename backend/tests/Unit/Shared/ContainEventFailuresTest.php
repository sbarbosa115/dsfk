<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Infrastructure\Bus\ContainEventFailures;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Middleware\StackMiddleware;

final class ContainEventFailuresTest extends TestCase
{
    public function testAFailingEventHandlerIsLoggedAndTheActionCarriesOn(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<string> */
            public array $lines = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->lines[] = $level.': '.$message;
            }
        };
        $failing = new class implements MiddlewareInterface {
            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                throw new \RuntimeException('SMTP down');
            }
        };
        $envelope = new Envelope(new \stdClass());

        $result = (new ContainEventFailures($logger))->handle($envelope, new StackMiddleware($failing));

        self::assertSame($envelope, $result);
        self::assertSame(['error: Event handler failed: SMTP down'], $logger->lines);
    }
}
