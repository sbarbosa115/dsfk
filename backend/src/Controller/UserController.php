<?php

namespace App\Controller;

use App\Dto\UserInput;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    private const CONTEXT = ['groups' => ['user:read']];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->users->findBy([], ['fullName' => 'ASC']), context: self::CONTEXT);
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(validationGroups: ['Default', 'create'])] UserInput $input): JsonResponse
    {
        $this->assertEmailAvailable($input->email);

        $user = new User($input->email, $input->fullName);
        $user->setPassword($this->hasher->hashPassword($user, $input->password));
        $user->setAdmin($input->admin ?? false);
        $this->em->persist($user);
        $this->em->flush();

        return $this->json($user, 201, context: self::CONTEXT);
    }

    #[Route('/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function update(User $user, #[MapRequestPayload] UserInput $input, #[CurrentUser] User $me): JsonResponse
    {
        if ($user === $me && (false === $input->admin || false === $input->active)) {
            throw new UnprocessableEntityHttpException('You cannot remove your own admin access or disable yourself.');
        }

        if (null !== $input->email && mb_strtolower($input->email) !== $user->getEmail()) {
            $this->assertEmailAvailable($input->email);
            $user->setEmail($input->email);
        }
        if (null !== $input->fullName) {
            $user->setFullName($input->fullName);
        }
        if (null !== $input->password) {
            $user->setPassword($this->hasher->hashPassword($user, $input->password));
        }
        if (null !== $input->admin) {
            $user->setAdmin($input->admin);
        }
        if (null !== $input->active) {
            $user->setActive($input->active);
        }
        $this->em->flush();

        return $this->json($user, context: self::CONTEXT);
    }

    private function assertEmailAvailable(string $email): void
    {
        if (null !== $this->users->findOneBy(['email' => mb_strtolower(trim($email))])) {
            throw new UnprocessableEntityHttpException('email_taken');
        }
    }
}
