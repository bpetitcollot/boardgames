<?php

namespace App\Controller;

use App\Command\CreateGame;
use App\Entity\Boardgame;
use App\Entity\Game;
use App\Entity\Player;
use App\Entity\User;
use App\Form\GameType;
use App\Repository\GameRepository;
use App\Solitaire\Manager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    private Manager $gameManager;

    public function __construct(
        EntityManagerInterface      $entityManager,
        GameRepository $gameRepository,
        Manager $gameManager
    )
    {
        $this->entityManager = $entityManager;
        $this->gameRepository = $gameRepository;
        $this->gameManager = $gameManager;
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
            $game->setState($this->gameManager->createInitialState());
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
    #[Route('/{id}/delete', name: 'delete', requirements: ['id'=>'\w+'])]
    public function deleteGame(string $id): Response
    {
        $game = $this->gameRepository->find($id);
        if ($game->getCreatedBy() !== $this->getUser()) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $this->entityManager->remove($game);
        $this->entityManager->flush();

        return $this->redirectToRoute('solitaire_home');
    }
    #[Route('/{id}', name: 'show', requirements: ['id'=>'\w+'])]
    public function showGame(string $id): Response
    {
        $game = $this->gameRepository->find($id);

        return $this->render('solitaire/show.html.twig', [
            'game' => $game,
            'solitaire' => $this->gameManager->createSolitaireFromGame($game),
        ]);
    }
}