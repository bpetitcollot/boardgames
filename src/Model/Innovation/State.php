<?php

namespace App\Model\Innovation;

use App\Entity\Action;
use App\Entity\Player;

class State
{

    const ACTION_DRAW = 'draw';
    const ACTION_PLACE = 'place';
    const ACTION_DOMINATE = 'dominate';
    const ACTION_ACTIVATE = 'activate';
    const ACTION_DRAW_TO_HAND = 'drawToHand';
    const ACTION_DRAW_AND_SCORE = 'drawAndScore';
    const ACTION_DRAW_AND_ARCHIVE = 'drawAndArchive';
    const ACTION_DRAW_AND_PLACE = 'drawAndPlace';
    const ACTION_BONUS_COOP = 'bonusCoop';
    const ACTION_RECYCLE = 'recycle';
    const ACTION_ARCHIVE = 'archive';
    const ACTION_SCORE = 'score';
    const ACTION_SPLAY = 'splay';
    const ACTION_REPEAT = 'repeat';
    const ACTION_TISSAGE_DOGMA_2 = 'tissageDogma2';
    const ACTION_TRANSFER = 'transfer';
    const ACTION_TRANSFER_CARD_TO = 'transferCardTo';
    const ACTION_ACCEPT = 'accept';
    const ACTION_PARAM_NO_DECLINE = 'noDecline';

// components containers
    protected $cards;
    protected $ages;
    protected $dominations;
    protected $civilizations;
// datas
    protected $bonusCoop;
    protected $activationDatas;
    protected $actionDeclined;
    protected $history;
    protected $gameOver;

    public function __construct()
    {
        $this->cards = array();
        $this->ages = array();
        for ($i = 1; $i <= 10; $i++) {
            $this->ages[$i] = new Stack('age ' . $i, null);
        }
        foreach (Card::AGE_CARDS as $name => $caracs) {
            $card = new Card($caracs['age'], $caracs['color'], $name, $caracs['resources']);
            $this->cards[$name] = $card;
            $this->ages[$caracs['age']]->addOnTop($card);
        }
        $this->dominations = new Set('dominations', null);
        $this->dominations->add(new Card(null, null, 'technologies', null));
        $this->dominations->add(new Card(null, null, 'militaire', null));
        $this->dominations->add(new Card(null, null, 'diplomatie', null));
        $this->dominations->add(new Card(null, null, 'culture', null));
        $this->dominations->add(new Card(null, null, 'sciences', null));
        $this->civilizations = array();
        $this->bonusCoop = false;
        $this->actionDeclined = false;
        $this->activationDatas = null;
        $this->gameOver = false;
        $this->history = array();
    }

    public function init($shuffles, $players)
    {
        foreach ($this->ages as $age => $stack) {
            $stack->rearrange($shuffles[$age]);
        }
        for ($i = 1; $i <= 9; $i++) {
            $this->dominations->add($this->drawInAge($i));
        }
        foreach ($players as $player) {
            $civilization = new Civilization($player);
            $civilization->addToHand($this->drawInAge(1));
            $civilization->addToHand($this->drawInAge(1));
            $this->civilizations[] = $civilization;
        }
    }

    public function getCards()
    {
        return $this->cards;
    }

    public function getCard($cardName)
    {
        return array_key_exists($cardName, $this->cards) ? $this->cards[$cardName] : null;
    }

    public function getAges()
    {
        return $this->ages;
    }

    public function getDominations()
    {
        return $this->dominations;
    }

    public function getCivilizations()
    {
        return $this->civilizations;
    }

    public function isGameOver()
    {
        return $this->gameOver;
    }

    public function setGameOver()
    {
        $this->gameOver = true;
    }

    public function getHistory()
    {
        return $this->history;
    }

    public function getCivilization($id)
    {
        if ($id === 'active')
            return $this->activationDatas['civilization'];
        foreach ($this->civilizations as $civilization) {
            if ($civilization->getId() === $id)
                return $civilization;
        }

        return null;
    }

    public function getPlayerCivilization(Player $player)
    {
        foreach ($this->civilizations as $civilization) {
            if ($civilization->getPlayer() === $player)
                return $civilization;
        }

        return null;
    }

    public function otherCivilizations(Civilization $civilization)
    {
        return array_filter($this->civilizations, function($civ) use ($civilization) {
            return $civ !== $civilization;
        });
    }

    public function getNextCivilization(Civilization $civilization)
    {
        $key = array_search($civilization, $this->civilizations);
        return array_key_exists($key + 1, $this->civilizations) ? $this->civilizations[$key + 1] : $this->civilizations[0];
    }

    public function getDominatedCivs(Civilization $civilization, $resource)
    {
        $civilizations = array();
        $civ = $this->getNextCivilization($civilization);
        $countResources = $civilization->countResources()[$resource];
        while ($civ !== $civilization) {
            if ($civ->countResources()[$resource] < $countResources) {
                $civilizations[] = $civ;
            }
            $civ = $this->getNextCivilization($civ);
        }

        return $civilizations;
    }

    public function getUndominatedCivs(Civilization $civilization, $resource)
    {
        $civilizations = array();
        $civ = $this->getNextCivilization($civilization);
        $countResources = $civilization->countResources()[$resource];
        while ($civ !== $civilization) {
            if ($civ->countResources()[$resource] >= $countResources) {
                $civilizations[] = $civ;
            }
            $civ = $this->getNextCivilization($civ);
        }
        $civilizations[] = $civilization;

        return $civilizations;
    }

    public function getCivilizationHand(Civilization $civilization)
    {
        return array_map(function($card) {
            return $card->getName();
        }, $civilization->getHigherCardsInHand());
    }

    public function getLastRecycled()
    {
        return $this->activationDatas['recycled'][count($this->activationDatas['recycled']) - 1];
    }

    public function createAction(Player $player, $actionName, $choices = array())
    {
        if (!array_key_exists('name', $choices)) {
            $choices['name'] = array('type' => 'choice', 'choices' => array($actionName => $actionName));
        }
        $action = new Action();
        $action->setPlayer($player)
            ->setName($actionName)
            ->setChoices($choices);

        return $action;
    }

///////////////////
// HANDLE ACTION //
///////////////////

    /**
     * Executes action & her children
     * 
     * @param Action $action : action to execute
     * @param Action $stopBefore : action before which execution must stop
     * @param boolean $stop
     */
    public function execute(Action $action, Action $stopBefore = null, $stop = false)
    {
        $cardName = array_key_exists('card', $action->getParams()) && is_string($this->getActionParam($action, 'card')) ? $this->getActionParam($action, 'card') : '';
        $this->history[] = array('debug' => true, 'content' => $action->getId() . ' - ' . $action->getPlayer() . ' ' . $action->getName() . ' ' . ($cardName) . ($action->isDeclined() ? '(declined)' : ''));
        $stopNow = $stop || $action === $stopBefore || $this->IsGameOver();
        if (!$stopNow) {
            if ($action->isCompleted()) {
                $this->actionDeclined = ($action->isRequired() && $action->isDeclined());
                if ($action->isDeclined()) {
                    $this->history[] = array('debug' => false, 'content' => $action->getPlayer() . ' declines');
                } elseif ($this->checkConditions($action)) {
                    $argumentsArray = $this->buildArgumentsArray($action);
                    if (in_array(null, $argumentsArray)) {
                        dump($action->getName(), $argumentsArray, $action);
                    } else {
                        $actions = call_user_func_array(array($this, $action->getName()), $argumentsArray);
                        if (array_key_exists('supremacy', $action->getExtraDatas()))
                            $this->bonusCoop = !$action->getExtraDatas()['supremacy'];
                        if ($actions !== null && count($action->getChildren()) === 0) {
                            foreach ($actions as $child) {
                                $action->addChild($child);
                            }
                        }
                    }
                }
            }
            foreach ($action->getChildren() as $subAction) {
                $this->execute($subAction, $stopBefore, $stopNow);
            }
        }
    }

    public function checkConditions(Action $action)
    {
        return !array_key_exists('conditions', $action->getExtraDatas()) || array_reduce($action->getExtraDatas()['conditions'], function($carry, $condition) use ($action) {
                return $carry && $this->checkCondition($action, $condition);
            }, true);
    }

    public function checkCondition(Action $action, $condition)
    {
        if (array_key_exists('sufferedSupremacy', $condition))
            return $this->getPlayerCivilization($action->getPlayer())->sufferedSupremacy() === $condition['sufferedSupremacy'];
        elseif (array_key_exists('transfered', $condition))
            return (count($this->activationDatas['transfered']) > 0) === $condition['transfered'];
        else
            return false;
    }

    public function buildArgumentsArray(Action $action)
    {
        $arguments = array();
        if (in_array($action->getName(), array(self::ACTION_BONUS_COOP, self::ACTION_DRAW, self::ACTION_TISSAGE_DOGMA_2)))
            $arguments = array($this->getPlayerCivilization($action->getPlayer()));
        elseif (in_array($action->getName(), array(self::ACTION_PLACE, self::ACTION_ARCHIVE, self::ACTION_ACTIVATE, self::ACTION_RECYCLE, self::ACTION_SCORE)))
            $arguments = array($this->getPlayerCivilization($action->getPlayer()), $this->cards[$this->getActionParam($action, 'card')]);
        elseif (in_array($action->getName(), array(self::ACTION_DRAW_TO_HAND, self::ACTION_DRAW_AND_SCORE, self::ACTION_DRAW_AND_PLACE, self::ACTION_DRAW_AND_ARCHIVE)))
            $arguments = array($this->getPlayerCivilization($action->getPlayer()), $this->getActionParam($action, 'age'));
        elseif (in_array($action->getName(), array(self::ACTION_TRANSFER, self::ACTION_TRANSFER_CARD_TO))) {
            $destinationArray = array(
                'civilization' => $this->getActionParam($action, 'civilization'),
                'target' => $this->getActionParam($action, 'target'),
            );
            $arguments = array(
                $this->getPlayerCivilization($action->getPlayer()),
                $this->getCard($this->getActionParam($action, 'card')),
                $this->computeDestination($destinationArray, $action),
            );
        } elseif ($action->getName() === self::ACTION_SPLAY)
            $arguments = array($this->getPlayerCivilization($action->getPlayer()), $this->getActionParam($action, 'color'), $this->getActionParam($action, 'direction'));
        elseif ($action->getName() === self::ACTION_ACCEPT)
            $arguments = array($this->getPlayerCivilization($action->getPlayer()), $this->getActionParam($action, 'callback'));
        elseif ($action->getName() === self::ACTION_REPEAT)
            $arguments = array($action);

        return $arguments;
    }

    public function getActionParam(Action $action, $param)
    {
        if (array_key_exists($param, $action->getParams())) {
            return $action->getParams()[$param];
        } elseif (array_key_exists($param, $action->getExtraDatas())) {
            $rawData = $action->getExtraDatas()[$param];
            return is_array($rawData) ? $this->resolveChoice($action->getPlayer(), $rawData) : $rawData;
        }
        return null;
    }

    public function computeDestination($destination, $action)
    {
        $civilization = $this->getCivilization($destination['civilization']);
        $target = $destination['target'];
        if ($target === 'hand')
            return $civilization->getHand();
        elseif ($target === 'influence')
            return $civilization->getInfluence();
        elseif ($target === 'projects')
            return $civilization->getProjects();
        elseif ($target === 'game') {
            $cardChoice = array_key_exists('card', $action->getParams()) ? $this->getActionParam($action, 'card') : (array_key_exists('card', $action->getExtraDatas()) ? $action->getExtraDatas()['card'] : null);
            if ($cardChoice === null)
                return null;
            $card = $this->cards[is_string($cardChoice) ? $cardChoice : $this->resolveChoice($civilization->getPlayer(), $cardChoice)[0]];
            return $civilization->getStack($card->getColor());
        }
    }

    public function autoParam(Action $action)
    {
        foreach ($action->getChoices() as $choiceName => $choice) {
            if ($choiceName !== 'name') {
                $actualChoice = $this->resolveChoice($action->getPlayer(), $choice);
                if (count($actualChoice) === 1)
                    $action->setParam($choiceName, array_values($actualChoice)[0]);
            }
        }
    }

////////////////////////
// ACTION VALIDATIONS //
////////////////////////
    // Action is valid if player choices are allowed
    public function validateActionChoice($player, $choice, $choices)
    {
        $actualChoices = $this->resolveChoice($player, $choices);
        return in_array($choice, $actualChoices) || count($actualChoices) === 0;
    }

    public function resolveChoice($player, $choices)
    {
        if (!is_array($choices))
            $actualChoices = array($choices);
        elseif ($choices['type'] === 'choice')
            $actualChoices = $choices['choices'];
        elseif ($choices['type'] === 'callback')
            $actualChoices = call_user_func_array(array($this, $choices['method']), array($this->getPlayerCivilization($player), $choices['args'] ?? array()));
        else
            throw new \Exception('Unknown choice type.');

        if (count($actualChoices) === 0)
            $actualChoices = array(null);

        return array_values($actualChoices);
    }

    // CHOICES RESOLVERS : return an actual array of choices //
    // Card choices

    public function cardFromHand(Civilization $civilization, $filters = array())
    {
        $cards = $this->applyCardChoiceFilters($civilization, $civilization->getHand()->getElements(), $filters);

        return array_map(function($card) {
            return $card->getName();
        }, $cards);
    }

    public function cardActive(Civilization $civilization, $filters = array())
    {
        $cards = $this->applyCardChoiceFilters($civilization, $civilization->getActiveCards(false), $filters);

        return array_map(function($card) {
            return $card->getName();
        }, $cards);
    }

    public function applyCardChoiceFilters(Civilization $civilization, $cards = array(), $filters = array())
    {
        $result = $cards;
        if (array_key_exists('havingResource', $filters)) {
            $resource = $filters['havingResource'];
            $result = array_filter($result, function($card) use ($resource) {
                return $card->hasResource($resource);
            });
        }
        if (array_key_exists('age', $filters)) {
            if (is_int($filters['age']))
                $age = $filters['age'];
            elseif ($filters['age'] === 'highestAgeInHand')
                $age = $civilization->getHighestAgeInHand();
            $result = array_filter($result, function($card) use ($age) {
                return $card->getAge() === $age;
            });
        }
        if (array_key_exists('color', $filters)) {
            $color = $filters['color'];
            $result = array_filter($result, function($card) use ($color) {
                return $card->getColor() === $color;
            });
        }
        if (array_key_exists('emptyColor', $filters)) {
            $empty = $filters['emptyColor'];
            $result = array_filter($result, function($card) use ($civilization, $empty) {
                return $civilization->getStack($card->getColor())->isEmpty() === $empty;
            });
        }

        return $result;
    }

    // Color choices

    public function colorLastArchived(Civilization $civilization)
    {
        return array($civilization->getLastArchived()->getColor());
    }

    // Age choices

    public function ageCountCivRecycledCards(Civilization $civilization, $modificators = array())
    {
        array($this->applyAgeModificators(count($civilization->getPlayer()->getRecycled()), $modificators));
    }

    public function ageLastRecycled(Civilization $civilization, $modificators = array())
    {
        return array($this->applyAgeModificators($this->getLastRecycled()->getAge(), $modificators));
    }

    public function applyAgeModificators($age, $modificators)
    {
        foreach ($modificators as $modificator => $value) {
            if ($modificator === 'add')
                $age += $value;
        }

        return $age;
    }

////////////////////////

    public function change(Civilization $civilization)
    {
        $this->bonusCoop |= ($civilization !== $this->activationDatas['civilization']);
        $civilization->sufferSupremacy(true);
    }

    /** Draw one card in $age stack or further.
     * Returns drawn card or null if no card could be drawn
     * 
     * @param int $age
     * @return Card | null
     */
    public function drawInAge($age)
    {
        if ($age > 10)
            return null;
        elseif ($age < 1 || $this->ages[$age]->isEmpty())
            return $this->drawInAge($age + 1);
        else
            return $this->ages[$age]->pickOnTop();
    }

    public function drawToHand(Civilization $civilization, $age, $public = false)
    {
        $card = $this->drawInAge($age);
        if ($card === false) {
            $this->setGameOver();
        } else {
            $this->change($civilization);
            $this->history[] = array('debug' => false, 'content' => $civilization . ' draws ' . ($public ? $card->getName() : 'a ' . $card->getAge()) . ' => hand');
            $civilization->addToHand($card);
        }

        return $card ?? null;
    }

    public function drawAndScore(Civilization $civilization, $age)
    {
        $card = $this->drawInAge($age);
        if ($card === false) {
            $this->setGameOver();
        } else {
            $this->change($civilization);
            $this->history[] = array('debug' => false, 'content' => $civilization . ' draws & scores ' . $card->getName());
            $civilization->score($card);
        }

        return $card ?? null;
    }

    public function drawAndArchive(Civilization $civilization, $age)
    {
        $card = $this->drawInAge($age);
        if ($card === false) {
            $this->setGameOver();
        } else {
            $this->change($civilization);
            $this->history[] = array('debug' => false, 'content' => $civilization . ' draws & archives ' . $card->getName());
            $civilization->archive($card);
        }

        return $card ?? null;
    }

    public function drawAndPlace(Civilization $civilization, $age)
    {
        $card = $this->drawInAge($age);
        if ($card === false) {
            $this->setGameOver();
        } else {
            $this->change($civilization);
            $this->history[] = array('debug' => false, 'content' => $civilization . ' draws ' . $card->getName() . ' => in game');
            $civilization->place($card);
        }

        return $card ?? null;
    }

    public function repeat(Action $repeat)
    {
        $action = $repeat->getParents(function($action) {
            return array_key_exists('repeat', $action->getExtraDatas()) && $action->getExtraDatas()['repeat'] = true;
        });
        $repeatedAction = $this->createAction($action->getPlayer(), $action->getName(), $action->getChoices())->setExtraDatas($action->getExtraDatas());
        $actions = array($repeatedAction);
        $child = $action->getChildren()[0];
        while ($child !== $repeat) {
            $repeatedChild = $this->createAction($child->getPlayer(), $action->getName(), $child->getChoices())->setExtraDatas($child->getExtraDatas());
            $repeatedAction->addChild($repeatedChild);
            $repeatedAction = $repeatedChild;
            $child = $repeatedAction->getChildren()[0];
        }
        $repeatedChild = $this->createAction($repeat->getPlayer(), $repeat->getName(), $child->getChoices())->setExtraDatas($repeat->getExtraDatas());
        $repeatedAction->addChild($repeatedChild);

        return $actions;
    }

/////////////
// ACTIONS //
/////////////

    public function bonusCoop(Civilization $civilization)
    {
        if ($this->bonusCoop && !$this->gameOver) {
            $this->draw($civilization);
            $this->history[count($this->history) - 1]['content'] .= ' (coop)';
        }
    }

    /**
     * Main action : draw
     * 
     * @param Civilization $civilization
     */
    public function draw(Civilization $civilization)
    {
        $card = $this->drawInAge($civilization->getAge());
        if ($card === false) {
            $this->setGameOver();
        } else {
            $this->change($civilization);
            $this->history[] = array('debug' => false, 'content' => $civilization . ' draws ' . $card->getName());
            $civilization->addToHand($card);
        }

        return null;
    }

    /**
     * Main action : dominate
     * 
     * @param Civilization $civilization
     * @param Card $card
     */
    public function dominate(Civilization $civilization, Card $card)
    {

        return null;
    }

    /**
     * Main action : activate
     * 
     * @param Card $card
     */
    public function activate(Civilization $civilization, Card $card)
    {
        $this->bonusCoop = false;
        $this->activationDatas = array(
            'civilization' => $civilization,
            'card' => $card->getName(),
            'recycled' => array(),
            'transfered' => array(),
        );
        $this->history[] = array('debug' => false, 'content' => $civilization . ' activates ' . $card->getName());
        $actions = call_user_func(array($this, $card->getName()), $civilization);
        $bonus = new Action();
        $bonus->setPlayer($civilization->getPlayer())
            ->setName(self::ACTION_BONUS_COOP);
        $actions[] = $bonus;

        return $actions;
    }

    /**
     * Main action : place
     * 
     * @param Card $card
     */
    public function place(Civilization $civilization, Card $card)
    {
        $civilization->place($card);
        $this->change($civilization);
        $this->history[] = array('debug' => false, 'content' => $civilization . ' places ' . $card->getName());
    }

    public function recycle(Civilization $civilization, Card $card)
    {
        $this->ages[$card->getAge()]->addAtBottom($card);
        $civilization->recycle($card);
        $this->change($civilization);
        $this->activationDatas['recycled'][] = $card;
        $this->history[] = array('debug' => false, 'content' => $civilization . ' recycles ' . $card->getName());
    }

    public function transfer(Civilization $civilization, Card $card, Set $destination)
    {
        $origin = $card->getContainer();
        if ($destination instanceof Stack) {
            $destination->addOnTop($card);
        } else {
            $destination->add($card);
        }
        $this->change($civilization);
        $this->activationDatas['transfered'][] = $card;
        $this->history[] = array('debug' => false, 'content' => $civilization . ' transfers ' . $card->getName() . ' from ' . $origin . ' to ' . $destination);
    }

    public function transferCardTo(Civilization $civilization, Card $card, Set $destination)
    {
        return $card !== null ? $this->transfer($civilization, $card, $destination) : null;
    }

    public function archive(Civilization $civilization, Card $card)
    {
        $civilization->archive($card);
        $this->change($civilization);
        $this->history[] = array('debug' => false, 'content' => $civilization . ' archives ' . $card->getName());
    }

    public function score(Civilization $civilization, Card $card)
    {
        $civilization->score($card);
        $this->change($civilization);
        $this->history[] = array('debug' => false, 'content' => $civilization . ' scores ' . $card->getName());
    }

    public function splay(Civilization $civilization, $color, $direction)
    {
        $stack = $civilization->getStack($color);
        $stack->splay($direction);
        $this->change($civilization);
        $this->history[] = array('debug' => false, 'content' => $civilization . ' splays his ' . $stack . ' to the ' . $direction);
    }

    public function accept(Civilization $civilization)
    {
        call_user_func(array($this, $callback), $civilization);
    }

//////////////////////
// CARDS ACTIVATION //
//////////////////////

    public function la_roue(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ) {
            for ($i = 1; $i <= 2; $i++) {
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                    'age' => array(
                        'type' => 'choice',
                        'choices' => array(1),
                    ),
                ));
            }
        }

        return $actions;
    }

    public function tissage(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                'card' => array(
                    'type' => 'callback',
                    'method' => 'cardFromHand',
                    'args' => array('emptyColor' => true),
                ),
            ));
        }
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TISSAGE_DOGMA_2)->setRequired(true);
        }

        return $actions;
    }

    public function tissageDogma2(Civilization $civilization)
    {
        foreach ($civilization->getStacks() as $color => $stack) {
            if (!$stack->isEmpty() && array_reduce($this->civilizations, function($carry, $civ) use($civilization, $color) {
                    return $carry && ($civ === $civilization || $civ->getStack($color)->isEmpty());
                }, true)) {
                $this->drawToHand($civilization, $civilization->getAge());
            }
        }
    }

    public function voiles(Civilization $civilization)
    {
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ) {
            $this->drawAndPlace($civ, 1);
        }
    }

    public function elevage(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                'card' => array(
                    'type' => 'callback',
                    'method' => 'cardFromHand',
                ),
            ));
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                'age' => array(
                    'type' => 'choice',
                    'choices' => array(1),
                ),
            ));
        }

        return $actions;
    }

    public function agriculture(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_SCORE, array(
                        'age' => array(
                            'type' => 'callback',
                            'method' => 'ageLastRecycled',
                            'args' => array('add' => 1),
                        ),
                    ))
                    ->setExtraDatas(array(
                        State::ACTION_PARAM_NO_DECLINE => true,
            )));
        }

        return $actions;
    }

    public function maconnerie(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromHand',
                        'args' => array('havingResource' => Card::RESOURCE_STONE),
                    ),
                    )
                )->setRequired(false)
                ->setExtraDatas(array('repeat' => true))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_REPEAT)->setExtraDatas(array(State::ACTION_PARAM_NO_DECLINE => true)));
        }

        return $actions;
    }

    public function metallurgie(Civilization $civilization)
    {
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ) {
            $stop = $this->gameOver;
            while (!$stop) {
                $card = $this->drawToHand($civ, 1);
                $stop = !$card->hasResource(Card::RESOURCE_STONE) || $this->gameOver;
                if (!$stop && !$this->gameOver)
                    $this->score($civ, $card);
            }
        }
    }

    public function rames(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getDominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ) {
            $civ->sufferSupremacy(false);
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromHand',
                        'args' => array('havingResource' => Card::RESOURCE_CROWN),
                    ),
                ))->setExtraDatas(array('supremacy' => true, 'civilization' => 'active', 'target' => 'influence'))
                ->setRequired(true)
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                    'age' => array(
                        'type' => 'choice',
                        'choices' => array(1),
                    ),
                ))
                ->setExtraDatas(array('supremacy' => true, 'conditions' => array(array('sufferedSupremacy' => true))))
                )
            ;
        }
        foreach ($this->getUndominatedCivs($civilization, Card::RESOURCE_STONE) as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                    'age' => array(
                        'type' => 'choice',
                        'choices' => array(1),
                    ),
                ))
                ->setExtraDatas(array('conditions' => array(array('transfered' => false))));
        }

        return $actions;
    }

    public function archerie(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getDominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                    'age' => array(
                        'type' => 'choice',
                        'choices' => array(1),
                    ),
                ))
                ->setRequired(true)
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromHand',
                        'args' => array('age' => 'highestAgeInHand'),
                    ),
                ))->setExtraDatas(array('supremacy' => true, 'civilization' => 'active', 'target' => 'hand'))
                ->setRequired(true)
            );
        }

        return $actions;
    }

    public function cites_etats(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getDominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ) {
            if ($civ->countResources()[Card::RESOURCE_STONE] >= 4) {
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                        'card' => array(
                            'type' => 'callback',
                            'method' => 'cardActive',
                            'args' => array('havingResource' => Card::RESOURCE_STONE),
                        )
                    ))->setRequired(true)
                    ->setExtraDatas(array('supremacy' => true, 'civilization' => 'active', 'target' => 'game'))
                    ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                        'age' => array(
                            'type' => 'choice',
                            'choices' => array(1),
                        ),
                    ))
                    ->setRequired(true)
                    ->setExtraDatas(array('supremacy' => true, 'conditions' => array(array('sufferedSupremacy' => true))))
                );
            }
        }

        return $actions;
    }

    public function code_de_lois(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_ARCHIVE, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromHand',
                        'args' => array('emptyColor' => false),
                    )
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_SPLAY, array(
                    'color' => array(
                        'type' => 'callback',
                        'method' => 'colorLastArchived',
                    ),
                    'direction' => array(
                        'type' => 'choice',
                        'choices' => array(Stack::SPLAY_LEFT => Stack::SPLAY_LEFT),
                    ),
                ))->setExtraDatas(array(State::ACTION_PARAM_NO_DECLINE => true)));
        }

        return $actions;
    }

    public function mysticisme(Civilization $civilization)
    {
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ) {
            $card = $this->drawToHand($civ, 1, true);
            if (!$civ->getStack($card->getColor())->isEmpty()) {
                $this->place($civ, $card);
                $this->drawToHand($civ, 1);
            }
        }
    }

    public function outils(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ) {
            if (count($civ->getHand()->getElements()) >= 3) {
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                        'card' => array(
                            'type' => 'callback',
                            'method' => 'cardFromHand',
                        ),
                    ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                            'card' => array(
                                'type' => 'callback',
                                'method' => 'cardFromHand',
                            ),
                        ))->setRequired(true)
                        ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                        ->addChild($this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                                'card' => array(
                                    'type' => 'callback',
                                    'method' => 'cardFromHand',
                                ),
                            ))->setRequired(true)->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                            ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_PLACE, array(
                                    'age' => array(
                                        'type' => 'choice',
                                        'choices' => array(3),
                                    ),
                                ))
                                ->setRequired(true)
                                ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                )));
            }
        }
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromHand',
                        'args' => array('age' => 3),
                    ),
                ))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                        'age' => array(
                            'type' => 'choice',
                            'choices' => array(1),
                        ),
                    ))
                    ->setRequired(true)
                    ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                )
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                        'age' => array(
                            'type' => 'choice',
                            'choices' => array(1),
                        ),
                    ))
                    ->setRequired(true)
                    ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                )
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                    'age' => array(
                        'type' => 'choice',
                        'choices' => array(1),
                    ),
                ))
                ->setRequired(true)
                ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                )
            ;
        }

        return $actions;
    }

    public function ecriture(Civilization $civilization)
    {
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ) {
            $this->drawToHand($civ, 2);
        }
    }

    public function poterie(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ) {
            $civ->clearRecycled();
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                        'card' => array(
                            'type' => 'callback',
                            'method' => 'cardFromHand',
                        ),
                    ))->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                    ->addChild($this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                            'card' => array(
                                'type' => 'callback',
                                'method' => 'cardFromHand',
                            ),
                        ))->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                ))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_SCORE, array(
                    'age' => array(
                        'type' => 'callback',
                        'method' => 'ageCountCivRecycledCards',
                    ),
                ))
                ->setRequired(true)
                ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
            );
        }
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                    'age' => array(
                        'type' => 'choice',
                        'choices' => array(1),
                    ),
                ))
                ->setRequired(true);
        }

        return $actions;
    }

    public function construction(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getDominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array('supremacy' => true, 'civilization' => 'active', 'target' => 'hand'));
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array('supremacy' => true, 'civilization' => 'active', 'target' => 'hand'));
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                    'age' => array(
                        'type' => 'choice',
                        'choices' => array(2),
                    ),
                ))
                ->setRequired(true)
                ->setExtraDatas(array('supremacy' => true));
        }

        return $actions;
    }

    public function calendrier(Civilization $civilization)
    {
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ) {
            if (count($civ->getHand()->getElements()) < count($civ->getInfluence()->getElements())) {
                $this->drawToHand($civ, 3);
                $this->drawToHand($civ, 3);
            }
        }
    }

    public function cartographie(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getDominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromInfluence',
                        'args' => array('age' => 1),
                    )
                ))->setRequired(true)
                ->setExtraDatas(array('supremacy' => true, 'civilization' => 'active', 'target' => 'influence'));
        }
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_SCORE, array(
                    'age' => array(
                        'type' => 'choice',
                        'choices' => array(1),
                    ),
                ))
                ->setRequired(true)
                ->setExtraDatas(array('conditions' => array(array('transfered' => true))));
        }

        return $actions;
    }

    public function construction_de_canaux(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_ACCEPT)
                ->setExtraDatas(array('callback' => 'construction_de_canaux_dogma1'));
        }

        return $actions;
    }

    public function construction_de_canaux_dogma1(Civilization $civilization)
    {
        $higherCardsInHand = $civilization->getHigherCardsInHand();
        $higherCardsInInfluence = $civilization->getHigherCardsInInfluence();
        foreach ($higherCardsInHand as $card) {
            $this->transfer($civilization, $card, $civilization->getInfluence());
        }
        foreach ($higherCardsInInfluence as $card) {
            $this->transfer($civilization, $card, $civilization->getHand());
        }
    }

    public function philosophie(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY, array(
                'direction' => array(
                    'type' => 'choice',
                    'choices' => array(Stack::SPLAY_LEFT => Stack::SPLAY_LEFT),
                ),
            ));
        }
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SCORE, array(
                'card' => array(
                    'type' => 'callback',
                    'method' => 'cardFromHand'
                ),
            ));
        }

        return $actions;
    }

    public function reseau_routier(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                'card' => array(
                    'type' => 'callback',
                    'method' => 'cardFromHand',
                ),
            ));
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_TRANSFER_CARD_TO, array(
                    'civilization' => array(
                        'type' => 'choice',
                        'choices' => array_map(function($civ) {
                                return $civ->getId();
                            }, $this->otherCivilizations($civilization)),
                    ),
                    'target' => array(
                        'type' => 'choice',
                        'choices' => array('game' => 'game'),
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array(
                    self::ACTION_PARAM_NO_DECLINE => true,
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardActive',
                        'args' => array('color' => Card::COLOR_RED),
                    ),
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                        'card' => array(
                            'type' => 'callback',
                            'method' => 'reseauRoutierGreenCard',
                        ),
                    ))
                    ->setRequired(true)
                    ->setExtraDatas(array(
                        'civilization' => 'active',
                        'target' => 'game',
                    ))
                )
            );
        }

        return $actions;
    }

    public function reseauRoutierGreenCard()
    {
        $lastTransfered = $this->activationDatas['transfered'][count($this->activationDatas['transfered']) - 1];
        return array($lastTransfered->getContainer()->getOwner()->getActiveCard(Card::COLOR_GREEN));
    }

    public function fermentation(Civilization $civilization)
    {
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ) {
            $count = $civ->countResources()[Card::RESOURCE_TREE] / 2;
            for ($i = 1; $i <= $count; $i++) {
                $this->drawToHand($civ, 2);
            }
        }
    }

    public function mathematiques(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromHand',
                    )
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_PLACE, array(
                        'age' => array(
                            'type' => 'callback',
                            'method' => 'ageLastRecycled',
                            'args' => array('add' => 1)
                        ),
                    ))
                    ->setRequired(true)
                    ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
            );
        }

        return $actions;
    }

    public function monotheisme(Civilization $civilization)
    {
        $actions = array();
        $emptyColors = array_filter(array_keys($civilization->getStacks()), function($color) use ($civilization) {
            return $civilization->getStack($color)->isEmpty();
        });
        $civs = $this->getDominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ) {
            $cardChoices = array_filter($civ->getActiveCards(), function($card) use ($emptyColors) {
                return $card !== null && in_array($card->getColor(), $emptyColors);
            });
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => 'choice',
                    'choices' => $cardChoices,
                ))->setRequired(true)
                ->setExtraDatas(array('supremacy' => true, 'civilization' => 'active', 'target' => 'influence'))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_ARCHIVE, array(
                    'age' => array(
                        'type' => 'choice',
                        'choices' => array(1),
                    ),
                ))
                ->setRequired(true)
                ->setExtraDatas(array('supremacy' => true))
            );
        }
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ) {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_ARCHIVE, array(
                    'age' => array(
                        'type' => 'choice',
                        'choices' => array(1),
                    ),
                ))
                ->setRequired(true);
        }

        return $actions;
    }

    public function monnaie(Civilization $civilization)
    {
        
    }

}
