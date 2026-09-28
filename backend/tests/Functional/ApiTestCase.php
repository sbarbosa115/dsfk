<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Model\User;
use App\Identity\Infrastructure\Security\SecurityUser;
use App\Project\Domain\Model\Project;
use App\Project\Domain\Model\ProjectRole;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional API tests: a real kernel, the MySQL test database (each test rolled back), JSON in and out.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected const PASSWORD = 'secret-password';

    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = self::service(EntityManagerInterface::class);
        self::service(CacheItemPoolInterface::class, 'cache.rate_limiter')->clear();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $type
     *
     * @return T
     */
    protected static function service(string $type, ?string $id = null): object
    {
        $service = static::getContainer()->get($id ?? $type);
        \assert($service instanceof $type);

        return $service;
    }

    protected function createUser(string $email, bool $admin = false, bool $active = true, bool $superAdmin = false): User
    {
        $user = new User($email, ucfirst((string) strstr($email, '@', true)), self::service(PasswordHasher::class)->hash(self::PASSWORD), new \DateTimeImmutable());
        // Access is changed by someone; in fixtures that is an unsaved super admin.
        $user->changeAccess(User::firstSuperAdmin('fixtures@example.com', 'Fixtures', '', new \DateTimeImmutable()), admin: $admin || $superAdmin, superAdmin: $superAdmin, active: $active);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @param list<array{User, ProjectRole}> $members
     */
    protected function createProject(string $name = 'Edificio Central', array $members = [], string $currency = 'COP'): Project
    {
        $project = new Project($name, $currency, new \DateTimeImmutable());
        foreach ($members as [$user, $role]) {
            $project->assign((int) $user->getId(), $role);
        }
        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }

    protected function loginAs(User $user): void
    {
        $this->client->loginUser(SecurityUser::fromUser($user));
    }

    /**
     * @param array<string, mixed>|null $body
     */
    protected function request(string $method, string $uri, ?array $body = null, bool $csrfHeader = true): mixed
    {
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if ($csrfHeader) {
            $headers['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        }
        $this->client->request($method, $uri, server: $headers, content: null === $body ? null : (string) json_encode($body));
        $content = (string) $this->client->getResponse()->getContent();

        return '' === $content ? null : json_decode($content, true);
    }

    /**
     * Same as request(), for calls whose answer is a JSON object.
     *
     * @param array<string, mixed>|null $body
     *
     * @return array<string, mixed>
     */
    protected function json(string $method, string $uri, ?array $body = null): array
    {
        $data = $this->request($method, $uri, $body);
        self::assertIsArray($data, (string) $this->client->getResponse()->getContent());

        return $data;
    }

    /**
     * Same as request(), for calls whose answer is a JSON list.
     *
     * @return list<array<mixed>>
     */
    protected function jsonList(string $method, string $uri): array
    {
        $data = $this->request($method, $uri);
        self::assertIsList($data, (string) $this->client->getResponse()->getContent());
        $rows = [];
        foreach ($data as $row) {
            self::assertIsArray($row);
            $rows[] = $row;
        }

        return $rows;
    }

    protected function assertStatus(int $expected): void
    {
        self::assertSame($expected, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
    }

    protected function assertError(int $status, string $code): void
    {
        $this->assertStatus($status);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertSame($code, $data['error'] ?? null);
    }
}
