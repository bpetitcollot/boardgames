<?php

namespace App\Controller;

use App\Command\Register;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    private Request $request;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        RequestStack $requestStack,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    )
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/user/me', name: 'current_user')]
    public function getCurrentUser(): JsonResponse
    {
        return $this->json($this->getUser(), context: ['groups' => 'public-view']);
    }
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(): JsonResponse
    {
        $command = $this->serializer->deserialize($this->request->getContent(), Register::class, 'json');
        $errors = $this->validator->validate($command);
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], 400);
        }

        $user = new User($command->email, $command->username);
        $user->setPassword($this->passwordHasher->hashPassword($user, $command->plainPassword));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'user' => $this->serializer->normalize($user, null, ['groups' => 'public-view']),
        ]);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['errors' => 'missing credentials'], 401);
        }

        $user->setLastLoginAt(new \DateTime());

        return $this->json($this->serializer->normalize($user));
    }
}