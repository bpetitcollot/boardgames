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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/solitaire', name: 'solitaire_')]
class SolitaireController extends AbstractController
{
    private EntityManagerInterface $entityManager;
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
    #[Route('/{id}/action', name: 'add_action', methods: ['POST'], requirements: ['id'=>'\w+'])]
    public function addAction(Request $request, string $id): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        // get posted action
        $data = json_decode($request->getContent(), true);
        $move = $this->gameManager->deserializeAction($data['action']);
        // validate action
        $solitaire = $this->gameManager->createSolitaireFromGame($game);
        if (!$solitaire->validate($move)){
            return new JsonResponse(['error' => 'action is not valid'], Response::HTTP_BAD_REQUEST);
        }
        // apply action
        try {
            $solitaire->applyMove($move);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
        // store new action
        $game->setState($this->gameManager->createGameStateFromSolitaire($solitaire));
        $this->entityManager->flush();

        return $this->json($game->getState());
    }
}