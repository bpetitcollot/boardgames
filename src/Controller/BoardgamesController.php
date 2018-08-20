<?php

namespace App\Controller;

use App\Entity\Action;
use App\Entity\Boardgame;
use App\Entity\Game;
use App\Form\ActionType;
use App\Form\BoardgameType;
use App\Form\GameType;
use App\Model\GameManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class BoardgamesController extends Controller
{

    const RULES_MANAGERS = array(
        'innovation.basic' => 'Innovation',
    );

    /**
     * List of all available boardgames
     * Handle boardgame creation
     */
    public function index(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $boardgameRep = $em->getRepository(Boardgame::class);
        $boardgames = $boardgameRep->findAll();
        $boardgame = new Boardgame();
        $form = $this->createForm(BoardgameType::class, $boardgame, array('rulesManagers' => self::RULES_MANAGERS));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $boardgame->generateSlug();
            $em->persist($boardgame);
            $em->flush();

            return $this->redirectToRoute('boardgames_index');
        }

        return $this->render('index.html.twig', [
                'boardgames' => $boardgames,
                'form' => $form->createView(),
        ]);
    }

    /**
     * List of Innovation games
     * Handle game creation
     */
    public function boardgame(Request $request, GameManager $gm, $slug)
    {
        $em = $this->getDoctrine()->getManager();
        $boardgameRep = $em->getRepository(Boardgame::class);
        $boardgame = $boardgameRep->findOneBySlug($slug);
        $gameRep = $em->getRepository(Game::class);
        $games = $gameRep->findByBoardgame($boardgame);
        $game = $gm->createGame($boardgame);
        $rules = $this->get($boardgame->getRulesManager());
        $form = $this->createForm(GameType::class, $game, array('extensions' => $rules->getExtensions()));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $playerNumber = $form->get('participants')->getData();
            $gm->initPlayers($game, $playerNumber);
            $gm->joinGame($game, $this->getUser());
            $em->persist($game);
            $em->flush();

            return $this->redirectToRoute('boardgame_index', ['slug' => $slug]);
        }

        return $this->render($slug . '/index.html.twig', ['games' => $games, 'form' => $form->createView()]);
    }

    /**
     * Current user joins game
     */
    public function joinGame(GameManager $gm, Game $game)
    {
        $user = $this->getUser();
        if ($game->playedBy($user))
            return $this->redirectToRoute('game_show', ['slug' => $game->getBoardgame()->getSlug(), 'game' => $game->getId()]);
        if (!$game->isJoinable()) {
            $this->addFlash('danger', 'Vous ne pouvez pas rejoindre cette partie.');
            return $this->redirectToRoute('boardgame_index', ['slug' => $game->getBoardgame()->getSlug()]);
        }

        $gm->joinGame($game, $user);
        if ($game->isFull() && !$game->isStarted()) {
            $rules = $this->get($game->getBoardgame()->getRulesManager());
            $rules->startGame($game);
        }
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        $this->addFlash('notice', 'Vous avez rejoint la partie.');

        return $this->redirectToRoute('game_show', ['slug' => $game->getBoardgame()->getSlug(), 'game' => $game->getId()]);
    }

    public function leaveGame(GameManager $gm, Game $game)
    {
        if ($game->isStarted()) {
            $this->addFlash('warning', 'Vous ne pouvez pas quitter une partie démarrée.');
            return $this->redirectToRoute('boardgame_index', ['slug' => $game->getBoardgame()->getSlug()]);
        }

        $gm->leaveGame($game, $this->getUser());
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        $this->addFlash('notice', 'Vous avez quitté la partie.');

        return $this->redirectToRoute('boardgame_index', ['slug' => $game->getBoardgame()->getSlug()]);
    }

    public function reinitGame(GameManager $gm, Game $game)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($game->getActionsRoot());
        $rules = $this->get($game->getBoardgame()->getRulesManager());
        $rules->reinitGame($game);
        $em->flush();
        $this->addFlash('notice', 'La partie a été redémarrée.');

        return $this->redirectToRoute('boardgame_index', ['slug' => $game->getBoardgame()->getSlug()]);
    }

    /**
     * Show current state of a game
     */
    public function showGame($slug, Game $game, Request $request)
    {
        if ($game->getBoardgame()->getSlug() !== $slug)
            throw $this->createNotFoundException();
        if (!$game->isStarted())
            return $this->redirectToRoute('boardgame_index', ['slug' => $game->getBoardgame()->getSlug()]);

        $rules = $this->get($game->getBoardgame()->getRulesManager());
        $state = $rules->getCurrentState($game);
        $uncompletedActions = $game->getActionsRoot()->getUncompletedSubactions();
        $forms = array();
        foreach ($uncompletedActions as $action) {
            $forms[] = $this->createForm(ActionType::class, $action, array('required' => $action->isRequired(), 'action_choices' => array_key_exists('name', $action->getChoices()) ? $action->getChoices()['name']['choices'] : array($action->getName() => $action->getName())));
        }
        foreach ($forms as $form) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $handleAction = $rules->handleAction($game, $form->getData());
                if ($handleAction === true) {
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    $this->addFlash('info', 'Action enregistrée');
                } else {
                    $this->addFlash('error', 'Action non enregistrée.');
                }

                return $this->redirectToRoute('game_show', ['slug' => $game->getBoardgame()->getSlug(), 'game' => $game->getId()]);
            }
        }

        return $this->render($slug . '/game.html.twig', [
                'game' => $game,
                'state' => $state,
                'forms' => array_map(function($form) {
                        return $form->createView();
                    }, $forms),
        ]);
    }

    public function resetAction($slug, Game $game, Action $action)
    {
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
        $rules = $this->get($game->getBoardgame()->getRulesManager());
        $rules->createNextAction($game);
        $em->flush();

        return $this->redirectToRoute('game_show', ['slug' => $slug, 'game' => $game->getId()]);
    }

}
