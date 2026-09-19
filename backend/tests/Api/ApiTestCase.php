<?php

namespace App\Tests\Api;

use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\User;
use App\Enum\ProjectRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected const PASSWORD = 'secret-password';

    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function createUser(string $email, bool $admin = false, bool $active = true): User
    {
        $user = new User($email, ucfirst(strstr($email, '@', true)));
        $user->setAdmin($admin);
        $user->setActive($active);
        $user->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, self::PASSWORD));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function createProject(string $name = 'Edificio Central', array $members = []): Project
    {
        $project = new Project($name, 'COP');
        foreach ($members as [$user, $role]) {
            $project->addMember(new ProjectMember($project, $user, $role));
        }
        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }

    protected function loginAs(User $user): void
    {
        $this->client->loginUser($user);
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
        $this->client->request($method, $uri, server: $headers, content: null === $body ? null : json_encode($body));
        $content = $this->client->getResponse()->getContent();

        return '' === $content ? null : json_decode($content, true);
    }

    protected function assertStatus(int $expected): void
    {
        self::assertSame($expected, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
    }
}
