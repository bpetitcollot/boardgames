<?php

namespace App\Controller;

use App\Entity\Boardgame;
use App\Entity\Game;
use App\Form\ActionType;
use App\Form\GameType;
use App\Model\GameManager;
use App\Model\Innovation;
use App\Repository\ActionRepository;
use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class InnovationController extends AbstractController
{
    private $innovation;
    
    public function __construct(Innovation $innovation)
    {
        $this->innovation = $innovation;
    }

    public function index(Request $request, GameManager $gm)
    {
        $em = $this->getDoctrine()->getManager();
        $boardgameRep = $em->getRepository(Boardgame::class);
        $boardgame = $boardgameRep->findOneBySlug('innovation');
        $gameRep = $em->getRepository(Game::class);
        $games = $gameRep->findByBoardgame($boardgame);
        $game = $gm->createGame($boardgame);
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $playerNumber = $form->get('participants')->getData();
            $gm->initPlayers($game, $playerNumber);
            $gm->joinGame($game, $this->getUser());
            $em->persist($game);
            $em->flush();

            return $this->redirectToRoute('innovation_index');
        }

        return $this->render('innovation/index.html.twig', ['games' => $games, 'form' => $form->createView()]);
    }

    /**
     * Current user joins game
     */
    public function joinGame(GameManager $gm, GameRepository $gameRepository, $gameId)
    {
        $game = $gameRepository->findWithActions($gameId);
        $user = $this->getUser();
        if ($game->playedBy($user))
            return $this->redirectToRoute('game_show', ['gameId' => $game->getId()]);
        if (!$game->isJoinable()) {
            $this->addFlash('danger', 'Vous ne pouvez pas rejoindre cette partie.');
            return $this->redirectToRoute('innovation_index');
        }

        $gm->joinGame($game, $user);
        if ($game->isFull() && !$game->isStarted()) {
            $rules = $this->get($game->getBoardgame()->getRulesManager());
            $rules->startGame($game);
        }
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        $this->addFlash('notice', 'Vous avez rejoint la partie.');

        return $this->redirectToRoute('innovation_game_show', ['gameId' => $game->getId()]);
    }

    public function leaveGame(GameManager $gm, GameRepository $gameRepository, $gameId)
    {
        $game = $gameRepository->findWithActions($gameId);
        if ($game->isStarted()) {
            $this->addFlash('warning', 'Vous ne pouvez pas quitter une partie démarrée.');
            return $this->redirectToRoute('innovation_index');
        }

        $gm->leaveGame($game, $this->getUser());
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        $this->addFlash('notice', 'Vous avez quitté la partie.');

        return $this->redirectToRoute('innovation_index');
    }

    public function reinitGame(GameRepository $gameRepository, $gameId)
    {
        $game = $gameRepository->findWithActions($gameId);
        $em = $this->getDoctrine()->getManager();
        $em->remove($game->getActionsRoot());
        $this->innovation->reinitGame($game);
        $em->flush();
        $this->addFlash('notice', 'La partie a été redémarrée.');

        return $this->redirectToRoute('innovation_index');
    }

    /**
     * Show current state of a game
     */
    public function showGame(GameRepository $gameRepository, Request $request, $gameId)
    {
        $game = $gameRepository->findWithActions($gameId);
        if (!$game->isStarted())
            return $this->redirectToRoute('innovation_index');

        $state = $this->innovation->getCurrentState($game);
        $uncompletedActions = $game->getActionsRoot()->getUncompletedSubactions();
        $forms = array();
        foreach ($uncompletedActions as $action) {
            $forms[] = $this->createForm(ActionType::class, $action, array('required' => $action->isRequired(), 'action_choices' => array_key_exists('name', $action->getChoices()) ? $action->getChoices()['name']['choices'] : array($action->getName() => $action->getName())));
        }
        foreach ($forms as $form) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $handleAction = $this->innovation->handleAction($game, $form->getData());
                if ($handleAction === true) {
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    $this->addFlash('info', 'Action enregistrée');
                } else {
                    $this->addFlash('error', 'Action non enregistrée.');
                }

                return $this->redirectToRoute('innovation_game_show', ['gameId' => $game->getId()]);
            }
        }

        return $this->render('innovation/game.html.twig', [
                'game' => $game,
                'state' => $state,
                'forms' => array_map(function($form) {
                        return $form->createView();
                    }, $forms),
        ]);
    }

    public function resetAction(GameRepository $gameRepository, ActionRepository $actionRepository, $gameId, $actionId)
    {
        $game = $gameRepository->findWithActions($gameId);
        $action = $actionRepository->find($actionId);
        $em = $this->getDoctrine()->getManager();
        $found = false;
        foreach ($game->getActionsRoot()->getChildren() as $child) {
            if ($child === $action)
                $found = true;
            if ($found) {
                $game->getActionsRoot()->removeChild($child);
                $em->remove($child);
            }
        }
        $this->innovation->createNextAction($game);
        $em->flush();

        return $this->redirectToRoute('innovation_game_show', ['gameId' => $game->getId()]);
    }

}
