<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PlumbingTest extends WebTestCase
{
    public function testAWriteWithoutTheCsrfHeaderIsRefusedBeforeAnythingElse(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        self::assertResponseStatusCodeSame(403);
        self::assertSame(['error' => 'csrf_header_missing'], json_decode((string) $client->getResponse()->getContent(), true));
    }

    public function testAnUnknownApiPathIsAJson404NotTheSpa(): void
    {
        $client = self::createClient();

        $client->request('GET', '/api/nothing-here');

        self::assertResponseStatusCodeSame(404);
        self::assertSame('not_found', json_decode((string) $client->getResponse()->getContent(), true)['error'] ?? null);
    }

    public function testEveryOtherPathIsServedTheSpaSoClientRoutesSurviveAReload(): void
    {
        $client = self::createClient();

        $client->request('GET', '/projects/12');

        $status = $client->getResponse()->getStatusCode();
        self::assertContains($status, [200, 503], 'the SPA controller answers (503 only while the UI is not built)');
    }
}
