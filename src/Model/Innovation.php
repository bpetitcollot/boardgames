<?php

namespace App\Model;

use App\Entity\Action;
use App\Entity\Game;
use App\Model\Innovation\State;
use Exception;

class Innovation
{

    const EXTENSIONS = array(
        'innovation.echoes' => 'Echos',
    );

    public function getExtensions()
    {
        return self::EXTENSIONS;
    }

    public function startGame(Game $game)
    {
        $array1 = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];
        $array2 = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array3 = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array4 = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array5 = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array6 = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array7 = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array8 = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array9 = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array10 = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        for ($i = 1; $i <= 10; $i++) {
            shuffle(${'array' . $i});
        }
        $game->setParams(array(
            'shuffles' => array(
                1 => $array1,
                2 => $array2,
                3 => $array3,
                4 => $array4,
                5 => $array5,
                6 => $array6,
                7 => $array7,
                8 => $array8,
                9 => $array9,
                10 => $array10,
            )
        ));
        $actionsRoot = new Action();
        $game->setActionsRoot($actionsRoot);
        $this->createNextAction($game);
    }

    public function reinitGame(Game $game)
    {
        $actionsRoot = new Action();
        $game->setActionsRoot($actionsRoot);
        $this->createNextAction($game);
    }

    public function getCurrentState(Game $game)
    {
        $state = new State();
        $state->init($game->getParams()['shuffles'], $game->getPlayers());
        $state->execute($game->getActionsRoot());

        return $state;
    }

    public function getStateBeforeAction(Game $game, Action $action)
    {
        if ($game->getActionsRoot() !== $action->retrieveActionsRoot())
            throw new Exception('Impossible de retrouver cette action.');

        $state = new State();
        $state->init($game->getParams()['shuffles'], $game->getPlayers());
        $state->execute($game->getActionsRoot(), $action);

        return $state;
    }

    /**
     * Handle new action and try to automate following uncompleted actions
     * If game is over, removes uncompleted actions
     * Else if no uncompleted action remains, create new turn action
     * 
     * @param Game $game
     * @param Action $action
     * @param State $state
     * @return boolean : wether action was validated or not
     */
    public function handleAction(Game $game, Action $action, State $state = null)
    {
        // get state before action
        if ($state === null)
            $state = $this->getStateBeforeAction($game, $action);
        // validate action
        // if action is not completed, return  <---------------------------------------------------
        if (!$this->validateAction($action, $state))
            return false;
        // else execute action                                                                     |
        if ($action->isDeclined())
            $this->removeSubactionsOnDecline($action);
        $action->complete();
        $state->execute($action);
        if ($state->isGameOver()) {
            $game->getActionsRoot()->removeUncompletedSubactions();
        }
        // move state forward                                                                      |
        $nextAction = $action->nextAction();
        while ($nextAction !== null && $nextAction->isCompleted()) {
            $state->execute($nextAction);
        }
        // if state reaches an uncompleted action try to automate it and repeat ___________________|
        if ($nextAction !== null) {
            $state->autoParam($nextAction);
            $this->handleAction($game, $nextAction, $state);
        }
        // else create next turn action & return
        elseif (!$state->isGameOver()) {
            $this->createNextAction($game);
        }

        return true;
    }

    public function validateAction(Action $action, State $state)
    {
        if ($action->isDeclined() && (!$action->isRequired() || $action->getExtraData('autoCancelled') === true))
            return true;
        if ($action->getParent() !== null && $action->getParent()->getParent() === null) {
            if ($action->getName() === State::ACTION_BONUS_COOP)
                return true;
            if ($action->getName() === State::ACTION_PLACE)
                return array_key_exists('card', $action->getParams()) && $state->getCards()[$action->getParams()['card']]->getContainer() === $state->getPlayerCivilization($action->getPlayer())->getHand();
            if ($action->getName() === State::ACTION_ACTIVATE)
                return array_key_exists('card', $action->getParams()) && in_array($state->getCards()[$action->getParams()['card']], array_map(function($stack) {
                            return $stack->getTopElement();
                        }, $state->getPlayerCivilization($action->getPlayer())->getStacks()), true);
        }
        if ($action->getName() === State::ACTION_REARRANGE_STACK){
            return $action->isDeclined() || $state->validateStackRearrangement($action->getPlayer(), $action->getParams());
        }
        if ($action->getName() === State::ACTION_RECYCLE_MANY){
            return $action->isDeclined() || $state->validateRecycleMany($action->getParams()['cards'], $action->getExtraData('cards'));
        }
        if (count($action->getChoices()) > 0) {
            return array_reduce(array_keys($action->getChoices()), function($carry, $choice) use($action, $state) {
                return $carry &&
                    (array_key_exists($choice, $action->getParams()) && $state->validateActionChoice($action->getPlayer(), $action->getParams()[$choice], $action->getChoices()[$choice], $action->isDeclined())
                        || $choice === 'name' && in_array($action->getName(), $action->getChoices()['name']['choices'])
                    );
            }, true);
        } else
            return true;
    }

    public function removeSubactionsOnDecline(Action $action)
    {
        foreach ($action->getChildren() as $child) {
            if (array_key_exists(State::ACTION_PARAM_NO_DECLINE, $child->getExtraDatas()) && $child->getExtraDatas()[State::ACTION_PARAM_NO_DECLINE]) {
                $action->removeChild($child);
            }
        }
    }

    public function createNextAction(Game $game)
    {
        $actions = $game->getActionsRoot()->getChildren();
        $playerLastAction = count($actions) > 0 ? $actions[count($actions) - 1]->getPlayer() : null;
        $playerPreviousAction = count($actions) > 1 ? $actions[count($actions) - 2]->getPlayer() : $playerLastAction;
        $player = $playerLastAction !== $playerPreviousAction ? $playerLastAction : $game->getNextPlayer($playerLastAction);
        $newAction = new Action();
        $newAction->setPlayer($player)
            ->setRequired(true)
            ->setChoices(array('name' => array(
                    'type' => 'choice',
                    'choices' => array(
                        State::ACTION_DRAW => State::ACTION_DRAW,
                        State::ACTION_PLACE => State::ACTION_PLACE,
                        State::ACTION_DOMINATE => State::ACTION_DOMINATE,
                        State::ACTION_ACTIVATE => State::ACTION_ACTIVATE,
        ))));
        $game->getActionsRoot()->addChild($newAction);
    }

}
