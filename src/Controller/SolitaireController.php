<?php

namespace App\Controller;

use App\Command\CreateGame;
use App\Entity\Boardgame;
use App\Entity\Game;
use App\Entity\Player;
use App\Entity\User;
use App\Form\GameType;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/solitaire', name: 'solitaire_')]
class SolitaireController extends AbstractController
{
    private Request $request;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private GameRepository $gameRepository;

    public function __construct(
        RequestStack                $requestStack,
        SerializerInterface         $serializer,
        ValidatorInterface          $validator,
        EntityManagerInterface      $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        GameRepository $gameRepository,
    )
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->gameRepository = $gameRepository;
    }

    #[Route('/', name: 'home')]
    public function home(Request $request): Response
    {
        $games = $this->gameRepository->findBy(['type' => Boardgame::Solitaire->value]);

        $createGame = new CreateGame();
        $form = $this->createForm(GameType::class, $createGame);
        $form->handleRequest($request);
        if ($this->getUser() instanceof User && $form->isSubmitted() && $form->isValid()) {
            $game = new Game(Boardgame::Solitaire, $createGame->name, 1);
            $game->setCreatedBy($this->getUser());
            $player = new Player($game, $this->getUser());
            $this->entityManager->persist($game);
            $this->entityManager->persist($player);
            $this->entityManager->flush();

            return $this->redirectToRoute('solitaire_home');
        }

        return $this->render('solitaire/index.html.twig', [
            'games' => $games,
            'form' => $form->createView(),
        ]);
    }
}