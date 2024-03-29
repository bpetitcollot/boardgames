<?php

namespace App\Model\Innovation;

use App\Entity\Action;
use App\Entity\Player;
use Exception;
use TypeError;
use function dump;

class State
{

    const ACTION_DRAW             = 'draw';
    const ACTION_PLACE            = 'place';
    const ACTION_DOMINATE         = 'dominate';
    const ACTION_ACTIVATE         = 'activate';
    const ACTION_DRAW_TO_HAND     = 'drawToHand';
    const ACTION_DRAW_AND_SCORE   = 'drawAndScore';
    const ACTION_DRAW_AND_ARCHIVE = 'drawAndArchive';
    const ACTION_DRAW_AND_PLACE   = 'drawAndPlace';
    const ACTION_BONUS_COOP       = 'bonusCoop';
    const ACTION_RECYCLE          = 'recycle';
    const ACTION_RECYCLE_MANY     = 'recycleMany';
    const ACTION_ARCHIVE          = 'archive';
    const ACTION_SCORE            = 'score';
    const ACTION_SPLAY            = 'splay';
    const ACTION_REPEAT           = 'repeat';
    const ACTION_TRANSFER         = 'transfer';
    const ACTION_ACCEPT           = 'accept';
    const ACTION_REARRANGE_STACK  = 'rearrangeStack';
    const ACTION_CHOOSE_OPTION    = 'chooseOption';
    const ACTION_EXCHANGE_START   = 'startExchange';
    const ACTION_EXCHANGE_1       = 'exchange1';
    const ACTION_EXCHANGE_2       = 'exchange2';
    const ACTION_EXCHANGE_COMMIT  = 'commitExchange';
    const ACTION_TISSAGE_2        = 'tissage2';
    const ACTION_MONNAIE_2        = 'monnaie2';
    const ACTION_PAPIER_2         = 'papier2';
    const ACTION_OPTIQUE_2        = 'optique2';
    const ACTION_ALCHIMIE_2       = 'alchimie2';
    const ACTION_POUDRE_2         = 'poudre2';
    const ACTION_INVENTION_2      = 'invention2';
    const ACTION_CONSERVES_2      = 'conserves2';
    const ACTION_CLASSIFICATION_2 = 'classification2';
    const ACTION_DEMOCRATIE_2     = 'democratie2';
    const ACTION_ECLAIRAGE_2      = 'eclairage2';
    const ACTION_ELECTRICITE_2    = 'electricite2';

    const ACTION_PARAM_NO_DECLINE = 'noDecline';

// components containers
    protected $cards;
    protected $ages;
    protected $dominations;
    protected $civilizations;
    protected $selectedCard;
    protected $exchange;
// datas
    protected $bonusCoop;
    protected $activationDatas;
    protected $actionDeclined;
    protected $history;
    protected $gameOver;

    public function __construct()
    {
        $this->cards = array();
        $this->ages  = array();
        for ($i = 1; $i <= 10; $i++)
        {
            $this->ages[$i] = new Stack('age ' . $i, null);
        }
        foreach (Card::AGE_CARDS as $name => $caracs)
        {
            $card               = new Card($caracs['age'], $caracs['color'], $name, $caracs['resources']);
            $this->cards[$name] = $card;
            $this->ages[$caracs['age']]->addOnTop($card);
        }
        $this->dominations     = new Set('dominations', null);
        $this->dominations->add(new Card(null, null, 'technologies', null));
        $this->dominations->add(new Card(null, null, 'militaire', null));
        $this->dominations->add(new Card(null, null, 'diplomatie', null));
        $this->dominations->add(new Card(null, null, 'culture', null));
        $this->dominations->add(new Card(null, null, 'sciences', null));
        $this->civilizations   = array();
        $this->bonusCoop       = false;
        $this->actionDeclined  = false;
        $this->activationDatas = null;
        $this->gameOver        = false;
        $this->history         = array();
        $this->selectedCard    = null;
        $this->exchange        = array();
    }

    public function init($shuffles, $players)
    {
        foreach ($this->ages as $age => $stack)
        {
            $stack->rearrange($shuffles[$age]);
        }
        for ($i = 1; $i <= 9; $i++)
        {
            $this->dominations->add($this->drawInAge($i));
        }
        foreach ($players as $player)
        {
            $civilization          = new Civilization($player);
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
        foreach ($this->civilizations as $civilization)
        {
            if ($civilization->getId() === $id)
                return $civilization;
        }

        return null;
    }

    public function getPlayerCivilization(Player $player)
    {
        foreach ($this->civilizations as $civilization)
        {
            if ($civilization->getPlayer() === $player)
                return $civilization;
        }

        return null;
    }

    public function otherCivilizations(Civilization $civilization)
    {
        return array_filter($this->civilizations, function($civ) use ($civilization)
        {
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
        $civilizations  = array();
        $civ            = $this->getNextCivilization($civilization);
        $countResources = $civilization->countResources()[$resource];
        while ($civ !== $civilization)
        {
            if ($civ->countResources()[$resource] < $countResources)
            {
                $civilizations[] = $civ;
            }
            $civ = $this->getNextCivilization($civ);
        }

        return $civilizations;
    }

    public function getUndominatedCivs(Civilization $civilization, $resource)
    {
        $civilizations  = array();
        $civ            = $this->getNextCivilization($civilization);
        $countResources = $civilization->countResources()[$resource];
        while ($civ !== $civilization)
        {
            if ($civ->countResources()[$resource] >= $countResources)
            {
                $civilizations[] = $civ;
            }
            $civ = $this->getNextCivilization($civ);
        }
        $civilizations[] = $civilization;

        return $civilizations;
    }

//    @TODO REMOVE ?
//    public function getCivilizationHand(Civilization $civilization)
//    {
//        return array_map(function($card) {
//            return $card->getName();
//        }, $civilization->getHigherCardsInHand());
//    }
//
    public function getLastRecycled()
    {
        return count($this->activationDatas['recycled']) > 0 ? $this->activationDatas['recycled'][count($this->activationDatas['recycled']) - 1] : null;
    }

    public function getLastDrawn()
    {
        return $this->activationDatas['drawn'][count($this->activationDatas['drawn']) - 1];
    }

    public function createAction(Player $player, $actionName, $choices = array())
    {
        if (!array_key_exists('name', $choices))
        {
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
     * Executes action & its children
     * 
     * @param Action $action : action to execute
     * @param Action $stopBefore : action before which execution must stop
     * @param boolean $stop
     */
    public function execute(Action $action, Action $stopBefore = null, $stop = false)
    {
        $cardName        = array_key_exists('card', $action->getParams()) && is_string($this->getActionParam($action, 'card')) ? $this->getActionParam($action, 'card') : '';
        $this->history[] = array('debug' => true, 'content' => $action->getId() . ' - ' . $action->getPlayer() . ' ' . $action->getName() . ' ' . ($cardName) . ($action->isDeclined() ? '(declined)' : ''));
        $stopNow         = $stop || $action === $stopBefore || $this->IsGameOver();
        if (!$stopNow)
        {
            if ($action->isCompleted())
            {
                $this->actionDeclined = (!$action->isRequired() && $action->isDeclined());
                if ($action->isDeclined() && !$action->isRequired() && !$this->getActionParam($action, 'autoCancelled'))
                {
                    $this->history[] = array('debug' => false, 'content' => $action->getPlayer() . ' declines');
                }
                if (!$action->isDeclined())
                {
                    $argumentsArray = $this->buildArgumentsArray($action);
                    try
                    {
                        $actions         = call_user_func_array(array($this, $action->getName()), $argumentsArray);
                        if (array_key_exists('supremacy', $action->getExtraDatas()))
                            $this->bonusCoop = !$action->getExtraDatas()['supremacy'];
                        if ($actions !== null && count($action->getChildren()) === 0)
                        {
                            foreach ($actions as $child)
                            {
                                $action->addChild($child);
                            }
                        }
                    } catch (TypeError $e)
                    {
                        dump($action->getName(), $argumentsArray, $action);
                    }
                }
            }
            foreach ($action->getChildren() as $subAction)
            {
                $this->execute($subAction, $stopBefore, $stopNow);
            }
        }
    }

    public function checkConditions(Action $action)
    {
        return !array_key_exists('conditions', $action->getExtraDatas()) || array_reduce($action->getExtraDatas()['conditions'], function($carry, $condition) use ($action)
            {
                return $carry && $this->checkCondition($action, $condition);
            }, true);
    }

    public function checkCondition(Action $action, $condition)
    {
        if (array_key_exists('sufferedSupremacy', $condition))
            return $this->getPlayerCivilization($action->getPlayer())->sufferedSupremacy() === $condition['sufferedSupremacy'];
        elseif (array_key_exists('transfered', $condition))
            return (count($this->activationDatas['transfered']) > 0) === $condition['transfered'];
        elseif (array_key_exists('recycled', $condition))
            return (count($this->activationDatas['recycled']) > 0) === $condition['recycled'];
        elseif (array_key_exists('threeLastDrawnOfDifferentColors', $condition))
            return $this->getPlayerCivilization($action->getPlayer())->threeLastDrawOfDifferentColors() === $condition['threeLastDrawnOfDifferentColors'];
        elseif (array_key_exists('choosenOption', $condition))
            return $this->activationDatas['choosenOption'] === $condition['choosenOption'];
        elseif (array_key_exists('handSize', $condition))
            return $this->getPlayerCivilization($action->getPlayer())->getHandSize() === $condition['handSize'];
        else
            return false;
    }

    public function buildArgumentsArray(Action $action)
    {
        $arguments = array($this->getPlayerCivilization($action->getPlayer()));
        if (in_array($action->getName(), array(self::ACTION_PLACE, self::ACTION_ARCHIVE, self::ACTION_ACTIVATE, self::ACTION_RECYCLE, self::ACTION_SCORE)))
            $arguments = array(
                $this->getPlayerCivilization($action->getPlayer()),
                $this->getActionParam($action, 'card') ? $this->cards[$this->getActionParam($action, 'card')] : null,
            );
        elseif (in_array($action->getName(), array(self::ACTION_DRAW_TO_HAND, self::ACTION_DRAW_AND_SCORE, self::ACTION_DRAW_AND_PLACE, self::ACTION_DRAW_AND_ARCHIVE)))
            $arguments = array(
                $this->getPlayerCivilization($action->getPlayer()),
                $this->getActionParam($action, 'age'),
                $this->getActionParam($action, 'public', false)
            );
        elseif ($action->getName() ===self::ACTION_TRANSFER)
        {
            $destinationArray = array(
                'civilization' => $this->getActionParam($action, 'civilization'),
                'target'       => $this->getActionParam($action, 'target'),
            );
            $arguments        = array(
                $this->getPlayerCivilization($action->getPlayer()),
                $this->computeDestination($destinationArray, $action),
                $this->getCard($this->getActionParam($action, 'card')),
            );
        }
        elseif ($action->getName() === self::ACTION_SPLAY)
            $arguments = array(
                $this->getPlayerCivilization($action->getPlayer()),
                $this->getActionParam($action, 'color'),
                $this->getActionParam($action, 'direction')
            );
        elseif ($action->getName() === self::ACTION_ACCEPT)
            $arguments = array(
                $this->getPlayerCivilization($action->getPlayer()),
                $this->getActionParam($action, 'callback')
            );
        elseif ($action->getName() === self::ACTION_REPEAT)
            $arguments = array($action);
        elseif ($action->getName() === self::ACTION_EXCHANGE_START)
        {
            $container1Array = array(
                'civilization' => $this->getActionParam($action, 'civilization1'),
                'target'       => $this->getActionParam($action, 'target1'),
            );
            $container2Array = array(
                'civilization' => $this->getActionParam($action, 'civilization2'),
                'target'       => $this->getActionParam($action, 'target2'),
            );
            $arguments       = array(
                $this->getPlayerCivilization($action->getPlayer()),
                $this->computeDestination($container1Array, $action),
                $this->computeDestination($container2Array, $action),
            );
        }
        elseif (in_array($action->getName(), array(self::ACTION_EXCHANGE_1, self::ACTION_EXCHANGE_2)))
            $arguments = array(
                $this->getPlayerCivilization($action->getPlayer()),
                $this->getActionParam($action, 'card') ? $this->cards[$this->getActionParam($action, 'card')] : null,
            );
        elseif ($action->getName() === self::ACTION_REARRANGE_STACK)
            $arguments = array(
                $this->getPlayerCivilization($action->getPlayer()),
                $action->getParams()['color'],
                $action->getParams()['arrangement'],
            );
        elseif ($action->getName() === self::ACTION_CHOOSE_OPTION)
            $arguments = array(
                $this->getPlayerCivilization($action->getPlayer()),
                $action->getParams()['choosenOption'],
            );
        elseif ($action->getName() === self::ACTION_RECYCLE_MANY)
            $arguments = array_map(function($cardName){ return $this->getCard($cardName); }, $action->getParams()['cards']);

        return $arguments;
    }

    public function getActionParam(Action $action, $param, $default = null)
    {
        if (array_key_exists($param, $action->getParams()))
        {
            return $action->getParams()[$param];
        }
        elseif (array_key_exists($param, $action->getExtraDatas()))
        {
            $rawData = $action->getExtraDatas()[$param];
            return is_array($rawData) ? $this->resolveChoice($action->getPlayer(), $rawData)[0] : $rawData;
        }
        return $default;
    }

    public function computeDestination($destination, $action)
    {
        $civilization = $this->getCivilization($destination['civilization']);
        $target       = $destination['target'];
        if ($target === 'hand')
            return $civilization->getHand();
        elseif ($target === 'influence')
            return $civilization->getInfluence();
        elseif ($target === 'projects')
            return $civilization->getProjects();
        elseif ($target === 'game')
        {
            $cardChoice = array_key_exists('card', $action->getParams()) ? $this->getActionParam($action, 'card') : (array_key_exists('card', $action->getExtraDatas()) ? $action->getExtraDatas()['card'] : null);
            if ($cardChoice === null)
                return null;
            $card       = $this->cards[is_string($cardChoice) ? $cardChoice : $this->resolveChoice($civilization->getPlayer(), $cardChoice)[0]];
            return $civilization->getStack($card->getColor());
        }
    }

    public function autoParam(Action $action)
    {
        if (!$this->checkConditions($action))
        {
            $action->setDeclined(true);
            $action->addExtraDatas('autoCancelled', true);
        }
        else
        {

            foreach ($action->getChoices() as $choiceName => $choice)
            {
                if ($choiceName !== 'name')
                {
                    $actualChoice = $this->resolveChoice($action->getPlayer(), $choice);
                    if (count($actualChoice) === 1 && $action->isRequired())
                        $action->setParam($choiceName, array_values($actualChoice)[0]);
                    elseif (count($actualChoice) === 0)
                    {
                        $action->setDeclined(true);
                        $action->addExtraData('autoCancelled', true);
                    }
                }
            }
        }
    }

////////////////////////
// ACTION VALIDATIONS //
////////////////////////
    // Action is valid if player choices are allowed
    public function validateActionChoice($player, $choice, $choices, $declined = false)
    {
        $actualChoices = $this->resolveChoice($player, $choices);
        return in_array($choice, $actualChoices) || (count($actualChoices) === 0 && $declined);
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
            throw new Exception('Unknown choice type.');

//        if (count($actualChoices) === 0)
//            $actualChoices = array(null);

        return array_values($actualChoices);
    }

    public function validateStackRearrangement(Player $player, $params)
    {
        if (!array_key_exists('color', $params) && !array_key_exists('arrangement', $params)) return false;
        $color = $params['color'];
        $arrangement = $params['arrangement'];
        $ranks = array_keys($this->getPlayerCivilization($player)->getStack($color)->getElements());
        return is_array($arrangement)
            && count($arrangement) === count($this->getPlayerCivilization($player)->getStack($color)->getElements())
            && count(array_filter($arrangement, function($value, $key) use($ranks){
            return !in_array($value, $ranks) || !in_array($key, $ranks);
        }, ARRAY_FILTER_USE_BOTH)) === 0;
    }
    
    public function validateRecycleMany($recycled, $toRecycle)
    {
        return count(array_diff($recycled, $toRecycle)) === 0 && count(array_diff($toRecycle, $recycled)) === 0;
    }
    
    // CHOICES RESOLVERS : return an actual array of choices //
    // Card choices

    public function cardFromHand(Civilization $civilization, $filters = array())
    {
        $cards = $this->applyCardChoiceFilters($civilization, $civilization->getHand()->getElements(), $filters);

        return array_map(function($card)
        {
            return $card->getName();
        }, $cards);
    }

    public function cardFromInfluence(Civilization $civilization, $filters = array())
    {
        $cards = $this->applyCardChoiceFilters($civilization, $civilization->getInfluence()->getElements(), $filters);

        return array_map(function($card)
        {
            return $card->getName();
        }, $cards);
    }

    public function cardFromOpponentInfluence(Civilization $civilization, $filters = array())
    {
        $cards = array_map(function($civ) use ($filters){
            $this->applyCardChoiceFilters($civ, $civ->getInfluence()->getElements(), $filters);
        }, array_filter($this->civilizations, function($civ) use ($civilization){
            return $civilization->getId() !== $civ->getId();
        }));

        return array_map(function($card)
        {
            return $card->getName();
        }, $cards);
    }

    public function cardActiveCiv(Civilization $civilization, $filters = array())
    {
        return $this->cardActive($this->getCivilization($filters['civilization']), $filters);
    }

    public function cardActive(Civilization $civilization, $filters = array())
    {
        $cards = $this->applyCardChoiceFilters($civilization, $civilization->getActiveCards(false), $filters);

        return array_map(function($card)
        {
            return $card->getName();
        }, $cards);
    }

    public function cardUnder(Civilization $civilization, $filters = array())
    {
        $cards = $this->applyCardChoiceFilters($civilization, $civilization->getUnderCards(false), $filters);

        return array_map(function($card)
        {
            return $card->getName();
        }, $cards);
    }

    public function cardLastDrawn(Civilization $civilization, $filters = array())
    {
        $cards = $this->applyCardChoiceFilters($civilization, array($civilization->getLastDrawn()), $filters);

        return array_map(function($card)
        {
            return $card->getName();
        }, $cards);
    }

    public function applyCardChoiceFilters(Civilization $civilization, $cards = array(), $filters = array())
    {
        $result = $cards;
        if (array_key_exists('havingResource', $filters))
        {
            $resource = $filters['havingResource'];
            $result   = array_filter($result, function($card) use ($resource)
            {
                return $card->hasResource($resource);
            });
        }
        if (array_key_exists('notHavingResource', $filters))
        {
            $resource = $filters['notHavingResource'];
            $result   = array_filter($result, function($card) use ($resource)
            {
                return !$card->hasResource($resource);
            });
        }
        if (array_key_exists('lowestHavingResource', $filters))
        {
            $resource = $filters['lowestHavingResource'];
            $result   = array_filter($result, function($card) use ($resource)
            {
                return $card->hasResource($resource);
            });
            $age = min(array_map(function($card)
                {
                    return $card->getAge();
                }, $result));
            $result = array_filter($result, function($card)
            {
                return $card->getAge() === $age;
            });
        }
        if (array_key_exists('age', $filters))
        {
            if (is_int($filters['age']))
                $age    = $filters['age'];
            elseif ($filters['age'] === 'highestAgeInHand')
                $age    = $civilization->getHighestAgeInHand();
            elseif ($filters['age'] === 'lowestAgeInInfluence')
                $age    = $civilization->getLowestAgeInInfluence();
            elseif ($filters['age'] === 'highestAgeInInfluence')
                $age    = $civilization->getHighestAgeInInfluence();
            elseif ($filters['age'] === 'ageLastRecycled')
                $age    = $this->getLastRecycled() ? $this->getLastRecycled()->getAge() : 0;
            $result = array_filter($result, function($card) use ($age)
            {
                return $card->getAge() === $age;
            });
        }
        if (array_key_exists('color', $filters))
        {
            $color  = $filters['color'];
            $result = array_filter($result, function($card) use ($color)
            {
                return $card->getColor() === $color;
            });
        }
        if (array_key_exists('colors', $filters))
        {
            $colors = $filters['colors'];
            $result = array_filter($result, function($card) use ($colors)
            {
                return in_array($card->getColor(), $colors);
            });
        }
        if (array_key_exists('emptyColor', $filters))
        {
            $empty  = $filters['emptyColor'];
            $result = array_filter($result, function($card) use ($civilization, $empty)
            {
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

    public function colorLastScored(Civilization $civilization)
    {
        return array($civilization->getLastScored()->getColor());
    }

    public function colorSplayable(Civilization $civilization)
    {
        return array_filter(array_keys($civilization->getStacks()), function($color) use ($civilization)
        {
            return count($civilization->getStack($color)->getElements()) >= 2;
        });
    }

    public function colorSplayedRight(Civilization $civilization)
    {
        return array_filter(array_keys($civilization->getStacks()), function($color) use ($civilization)
        {
            return $civilization->getStack($color)->getSplay() === Stack::SPLAY_RIGHT;
        });
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

    public function ageActives(Civilization $civilization, $modificators = array())
    {
        $activeCards = $civilization->getActiveCards(true);
        return array_map(function($card) use ($modificators)
        {
            return $this->applyAgeModificators($card ? $card->getAge() : 0, $modificators);
        }, array_filter($activeCards, function($card) use ($modificators)
            {
                return !array_key_exists('colors', $modificators) || in_array($card->getColor(), $modificators['colors']);
            }));
    }

    public function highestAgeInInfluence(Civilization $civilization, $modificators = array())
    {
        return array($this->applyAgeModificators($civilization->getHighestAgeInInfluence(), $modificators));
    }

    public function currentAge(Civilization $civilization, $modificators = array())
    {
        return array($this->applyAgeModificators($civilization->getAge(), $modificators));
    }

    public function countLastStackSplayed(Civilization $civilization, $modificators = array())
    {
        return array($this->applyAgeModificators($civilization->countLastStackSplayed(), $modificators));
    }

    public function applyAgeModificators($age, $modificators)
    {
        foreach ($modificators as $modificator => $value)
        {
            if ($modificator === 'add')
                $age += $value;
        }

        return $age;
    }

    // Civ choices

    public function civWithLessInfluenceThan($max)
    {
        return array_filter($this->civilizations, function($civ) use ($max)
        {
            return $civ->countInfluence() < $max;
        });
    }

    // Civilization choices

    public function civHavingLessInfluence(Civilization $civilization)
    {
        return array_map(function($civ)
        {
            return $civ->getId();
        }, array_filter($this->civilizations, function($civ) use ($civilization)
            {
                return $civ !== $civilization && $civ->countInfluence() < $civilization->countInfluence();
            }));
    }

////////////////////////

    public function change(Civilization $civilization)
    {
        $this->bonusCoop |= (array_key_exists('civilization', $this->activationDatas) && $civilization !== $this->activationDatas['civilization']);
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
        {
            $card                             = $this->ages[$age]->pickOnTop();
            $this->activationDatas['drawn'][] = $card;
            return $card;
        }
    }

    public function drawToHand(Civilization $civilization, $age, $public = false)
    {
        $card = $this->drawInAge($age);
        if ($card === false)
        {
            $this->setGameOver();
        }
        else
        {
            $civilization->draw($card);
            $this->change($civilization);
            $this->history[] = array('debug' => false, 'content' => $civilization . ' draws ' . ($public ? $card->getName() : 'a ' . $card->getAge()) . ' => hand');
            $civilization->addToHand($card);
        }

        return $card ?? null;
    }

    public function drawAndScore(Civilization $civilization, $age)
    {
        $card = $this->drawInAge($age);
        if ($card === false)
        {
            $this->setGameOver();
        }
        else
        {
            $civilization->draw($card);
            $this->change($civilization);
            $this->history[] = array('debug' => false, 'content' => $civilization . ' draws & scores ' . $card->getName());
            $civilization->score($card);
        }

        return $card ?? null;
    }

    public function drawAndArchive(Civilization $civilization, $age)
    {
        $card = $this->drawInAge($age);
        if ($card === false)
        {
            $this->setGameOver();
        }
        else
        {
            $civilization->draw($card);
            $this->change($civilization);
            $this->history[] = array('debug' => false, 'content' => $civilization . ' draws & archives ' . $card->getName());
            $civilization->archive($card);
        }

        return $card ?? null;
    }

    public function drawAndPlace(Civilization $civilization, $age)
    {
        $card = $this->drawInAge($age);
        if ($card === false)
        {
            $this->setGameOver();
        }
        else
        {
            $civilization->draw($card);
            $this->change($civilization);
            $this->history[] = array('debug' => false, 'content' => $civilization . ' draws ' . $card->getName() . ' => in game');
            $civilization->place($card);
        }

        return $card ?? null;
    }

    public function repeat(Action $repeat)
    {
        $action = $repeat->getParents(function($action)
        {
            return array_key_exists('repeat', $action->getExtraDatas()) && $action->getExtraDatas()['repeat'] = true;
        });
        $repeatedAction = $this->createAction($action->getPlayer(), $action->getName(), $action->getChoices())->setExtraDatas($action->getExtraDatas());
        $actions        = array($repeatedAction);
        $child          = $action->getChildren()[0];
        while ($child !== $repeat)
        {
            $repeatedChild  = $this->createAction($child->getPlayer(), $action->getName(), $child->getChoices())->setExtraDatas($child->getExtraDatas());
            $repeatedAction->addChild($repeatedChild);
            $repeatedAction = $repeatedChild;
            $child          = $repeatedAction->getChildren()[0];
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
        if ($this->bonusCoop && !$this->gameOver)
        {
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
        if ($card === false)
        {
            $this->setGameOver();
        }
        else
        {
            $civilization->draw($card);
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
        $this->bonusCoop       = false;
        $this->activationDatas = array(
            'civilization' => $civilization,
            'card'         => $card->getName(),
            'recycled'     => array(),
            'transfered'   => array(),
            'drawn'        => array(),
        );
        $this->history[]       = array('debug' => false, 'content' => $civilization . ' activates ' . $card->getName());
        $actions               = call_user_func(array($this, $card->getName()), $civilization);
        $bonus                 = new Action();
        $bonus->setPlayer($civilization->getPlayer())
            ->setName(self::ACTION_BONUS_COOP);
        $actions[]             = $bonus;

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
        $this->history[]                     = array('debug' => false, 'content' => $civilization . ' recycles ' . $card->getName());
    }

    public function recycleMany(Civilization $civilization, $cards)
    {
        foreach ($cards as $card)
        {
            $this->recycle($civilization, $card);
        }
    }
    
    public function moveCard(Card $card, Set $destination)
    {
        if ($destination instanceof Stack)
        {
            $destination->addOnTop($card);
        }
        else
        {
            $destination->add($card);
        }
    }

    public function transfer(Civilization $civilization, Set $destination, Card $card = null)
    {
        if ($card !== null)
        {
            $origin                                = $card->getContainer();
            $this->moveCard($card, $destination);
            $this->change($civilization);
            $this->activationDatas['transfered'][] = $card;
            $this->history[]                       = array('debug' => false, 'content' => $civilization . ' transfers ' . $card->getName() . ' from ' . $origin . ' to ' . $destination);
        }
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
        $stack           = $civilization->getStack($color);
        $civilization->splay($color, $direction);
        $this->change($civilization);
        $this->history[] = array('debug' => false, 'content' => $civilization . ' splays his ' . $stack . ' to the ' . $direction);
    }

    public function accept(Civilization $civilization, $callback)
    {
        if ($callback !== null)
            call_user_func(array($this, $callback), $civilization);
    }

    public function startExchange(Civilization $civilization, Set $origin1, Set $origin2)
    {
        $this->exchange = array(
            1 => array('origin' => $origin1, 'cards' => array()),
            2 => array('origin' => $origin2, 'cards' => array()),
        );
    }

    public function exchange1(Civilization $civilization, Card $card = null)
    {
        if ($card !== null)
            $this->exchange[1]['cards'][] = $card;
    }

    public function exchange2(Civilization $civilization, Card $card = null)
    {
        if ($card !== null)
            $this->exchange[2]['cards'][] = $card;
    }

    public function commitExchange(Civilization $civilization)
    {
        if (count($this->exchange[1]['cards']) === 0 && count($this->exchange[2]['cards']) === 0)
            $this->history[] = array('debug' => false, 'content' => 'Nothing to exchange');
        else
        {
            $container1 = $this->exchange[1]['origin'];
            $container2 = $this->exchange[2]['origin'];
            foreach ($this->exchange[1]['cards'] as $card)
            {
                $this->moveCard($card, $container2);
            }
            foreach ($this->exchange[2]['cards'] as $card)
            {
                $this->moveCard($card, $container1);
            }
            $this->change($container1->getOwner());
            $this->change($container2->getOwner());
            $cardNames1         = count($this->exchange[1]['cards']) > 0 ? implode(', ', $this->exchange[1]['cards']) : 'nothing';
            $cardNames2         = count($this->exchange[2]['cards']) > 0 ? implode(', ', $this->exchange[2]['cards']) : 'nothing';
            $this->history[]    = array('debug' => false, 'content' => $civilization . ' exchanges ' . $cardNames1 . ' (from ' . $container1 . ') with ' . $cardNames2 . ' (from ' . $container2 . ')');
            $this->selectedCard = null;
        }
    }

    public function rearrangeStack(Civilization $civilization, $color, $arrangement)
    {
        $stack           = $civilization->getStack($color);
        $civilization->getStack($color)->rearrange($arrangement);
        $this->history[] = array('debug' => false, 'content' => $civilization . ' rearranger his ' . $stack);
    }
    
    public function exchangeSets(Civilization $civilization, Set $set1, Set $set2)
    {
        $set1Elements = $set1->getElements();
        $set1->setElements($set2->getElements());
        $set2->setElements($set1Elements);
        $this->history[] = array('debug' => false, 'content' => $civilization . ' exchanges ' . $set1.' with '.$set2);
    }
    
    public function chooseOption(Civilization $civilization, $option)
    {
        $this->activationDatas['choosenOption'] = $option;
    }
    
//////////////////////
// CARDS ACTIVATION //
//////////////////////
    // AGE 1

    public function la_roue(Civilization $civilization)
    {
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ)
        {
            $this->drawToHand($civ, 1);
            $this->drawToHand($civ, 1);
        }
    }

    public function tissage(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                'card' => array(
                    'type'   => 'callback',
                    'method' => 'cardFromHand',
                    'args'   => array('emptyColor' => true),
                ),
            ));
        }
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TISSAGE_2)->setRequired(true);
        }

        return $actions;
    }

    public function tissage2(Civilization $civilization)
    {
        foreach ($civilization->getStacks() as $color => $stack)
        {
            if (!$stack->isEmpty() && array_reduce($this->civilizations, function($carry, $civ) use($civilization, $color)
                {
                    return $carry && ($civ === $civilization || $civ->getStack($color)->isEmpty());
                }, true))
            {
                $this->drawToHand($civilization, $civilization->getAge());
            }
        }
    }

    public function voiles(Civilization $civilization)
    {
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $this->drawAndPlace($civ, 1);
        }
    }

    public function elevage(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                'card' => array(
                    'type'   => 'callback',
                    'method' => 'cardFromHand',
                ),
            ));
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                'age' => array(
                    'type'    => 'choice',
                    'choices' => array(1),
                ),
            ));
        }

        return $actions;
    }

    public function agriculture(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_SCORE, array(
                        'age' => array(
                            'type'   => 'callback',
                            'method' => 'ageLastRecycled',
                            'args'   => array('add' => 1),
                        ),
                    ))
                    ->setRequired(true)
                    ->setExtraDatas(array(
                        State::ACTION_PARAM_NO_DECLINE => true,
            )));
        }

        return $actions;
    }

    public function maconnerie(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                        'args'   => array('havingResource' => Card::RESOURCE_STONE),
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
        foreach ($civs as $civ)
        {
            $stop = $this->gameOver;
            while (!$stop)
            {
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
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ)
        {
            $civ->sufferSupremacy(false);
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                        'args'   => array('havingResource' => Card::RESOURCE_CROWN),
                    ),
                ))->setExtraDatas(array('supremacy' => true, 'civilization' => 'active', 'target' => 'influence'))
                ->setRequired(true)
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                    'age' => array(
                        'type'    => 'choice',
                        'choices' => array(1),
                    ),
                ))
                ->setExtraDatas(array('supremacy' => true, 'conditions' => array(array('sufferedSupremacy' => true))))
                )
            ;
        }
        foreach ($this->getUndominatedCivs($civilization, Card::RESOURCE_STONE) as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                    'age' => array(
                        'type'    => 'choice',
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
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                    'age' => array(
                        'type'    => 'choice',
                        'choices' => array(1),
                    ),
                ))
                ->setRequired(true)
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                        'args'   => array('age' => 'highestAgeInHand'),
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
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            if ($civ->countResources()[Card::RESOURCE_STONE] >= 4)
            {
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                        'card' => array(
                            'type'   => 'callback',
                            'method' => 'cardActive',
                            'args'   => array('havingResource' => Card::RESOURCE_STONE),
                        )
                    ))->setRequired(true)
                    ->setExtraDatas(array('supremacy' => true, 'civilization' => 'active', 'target' => 'game'))
                    ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                        'age' => array(
                            'type'    => 'choice',
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
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_ARCHIVE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                        'args'   => array('emptyColor' => false),
                    )
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_SPLAY, array(
                    'color'     => array(
                        'type'   => 'callback',
                        'method' => 'colorLastArchived',
                    ),
                    'direction' => array(
                        'type'    => 'choice',
                        'choices' => array(Stack::SPLAY_LEFT => Stack::SPLAY_LEFT),
                    ),
                ))->setExtraDatas(array(State::ACTION_PARAM_NO_DECLINE => true)));
        }

        return $actions;
    }

    public function mysticisme(Civilization $civilization)
    {
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ)
        {
            $card = $this->drawToHand($civ, 1, true);
            if (!$civ->getStack($card->getColor())->isEmpty())
            {
                $this->place($civ, $card);
                $this->drawToHand($civ, 1);
            }
        }
    }

    public function outils(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            if (count($civ->getHand()->getElements()) >= 3)
            {
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                        'card' => array(
                            'type'   => 'callback',
                            'method' => 'cardFromHand',
                        ),
                    ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                            'card' => array(
                                'type'   => 'callback',
                                'method' => 'cardFromHand',
                            ),
                        ))->setRequired(true)
                        ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                        ->addChild($this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                                'card' => array(
                                    'type'   => 'callback',
                                    'method' => 'cardFromHand',
                                ),
                            ))->setRequired(true)->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                            ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_PLACE, array(
                                    'age' => array(
                                        'type'    => 'choice',
                                        'choices' => array(3),
                                    ),
                                ))
                                ->setRequired(true)
                                ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                )));
            }
        }
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                        'args'   => array('age' => 3),
                    ),
                ))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                        'age' => array(
                            'type'    => 'choice',
                            'choices' => array(1),
                        ),
                    ))
                    ->setRequired(true)
                    ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                )
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                        'age' => array(
                            'type'    => 'choice',
                            'choices' => array(1),
                        ),
                    ))
                    ->setRequired(true)
                    ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                )
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                    'age' => array(
                        'type'    => 'choice',
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
        foreach ($civs as $civ)
        {
            $this->drawToHand($civ, 2);
        }
    }

    public function poterie(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            $civ->clearRecycled();
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                        'card' => array(
                            'type'   => 'callback',
                            'method' => 'cardFromHand',
                        ),
                    ))->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                    ->addChild($this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                            'card' => array(
                                'type'   => 'callback',
                                'method' => 'cardFromHand',
                            ),
                        ))->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                ))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_SCORE, array(
                    'age' => array(
                        'type'   => 'callback',
                        'method' => 'ageCountCivRecycledCards',
                    ),
                ))
                ->setRequired(true)
                ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
            );
        }
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                    'age' => array(
                        'type'    => 'choice',
                        'choices' => array(1),
                    ),
                ))
                ->setRequired(true);
        }

        return $actions;
    }

    // AGE 2

    public function construction(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array('supremacy' => true, 'civilization' => 'active', 'target' => 'hand'));
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array('supremacy' => true, 'civilization' => 'active', 'target' => 'hand'));
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                    'age' => array(
                        'type'    => 'choice',
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
        foreach ($civs as $civ)
        {
            if (count($civ->getHand()->getElements()) < count($civ->getInfluence()->getElements()))
            {
                $this->drawToHand($civ, 3);
                $this->drawToHand($civ, 3);
            }
        }
    }

    public function cartographie(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromInfluence',
                        'args'   => array('age' => 1),
                    )
                ))->setRequired(true)
                ->setExtraDatas(array('supremacy' => true, 'civilization' => 'active', 'target' => 'influence'));
        }
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_SCORE, array(
                    'age' => array(
                        'type'    => 'choice',
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
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_ACCEPT)
                ->setExtraDatas(array('callback' => 'construction_de_canaux_dogma1'));
        }

        return $actions;
    }

    public function construction_de_canaux_dogma1(Civilization $civilization)
    {
        $higherCardsInHand      = $civilization->getHigherCardsInHand();
        $higherCardsInInfluence = $civilization->getHigherCardsInInfluence();
        foreach ($higherCardsInHand as $card)
        {
            $this->transfer($civilization, $card, $civilization->getInfluence());
        }
        foreach ($higherCardsInInfluence as $card)
        {
            $this->transfer($civilization, $card, $civilization->getHand());
        }
    }

    public function philosophie(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY, array(
                    'color' => array(
                        'type'   => 'callback',
                        'method' => 'colorSplayable',
                    ),
                ))->setExtraDatas(array(
                'direction' => Stack::SPLAY_LEFT,
            ));
        }
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SCORE, array(
                'card' => array(
                    'type'   => 'callback',
                    'method' => 'cardFromHand'
                ),
            ));
        }

        return $actions;
    }

    public function reseau_routier(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                'card' => array(
                    'type'   => 'callback',
                    'method' => 'cardFromHand',
                ),
            ));
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'civilization' => array(
                        'type'    => 'choice',
                        'choices' => array_map(function($civ)
                            {
                                return $civ->getId();
                            }, $this->otherCivilizations($civilization)),
                    ),
                    'target'   => 'game',
                ))->setRequired(true)
                ->setExtraDatas(array(
                    self::ACTION_PARAM_NO_DECLINE => true,
                    'card'                        => array(
                        'type'   => 'callback',
                        'method' => 'cardActive',
                        'args'   => array('color' => Card::COLOR_RED),
                    ),
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                        'card' => array(
                            'type'   => 'callback',
                            'method' => 'reseauRoutierGreenCard',
                        ),
                    ))
                    ->setRequired(true)
                    ->setExtraDatas(array(
                        'civilization' => 'active',
                        'target'       => 'game',
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
        foreach ($civs as $civ)
        {
            $count = $civ->countResources()[Card::RESOURCE_TREE] / 2;
            for ($i = 1; $i <= $count; $i++)
            {
                $this->drawToHand($civ, 2);
            }
        }
    }

    public function mathematiques(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    )
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_PLACE, array(
                        'age' => array(
                            'type'   => 'callback',
                            'method' => 'ageLastRecycled',
                            'args'   => array('add' => 1)
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
        $actions     = array();
        $emptyColors = array_filter(array_keys($civilization->getStacks()), function($color) use ($civilization)
        {
            return $civilization->getStack($color)->isEmpty();
        });
        $civs = $this->getDominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ)
        {
            $cardChoices = array_filter($civ->getActiveCards(), function($card) use ($emptyColors)
            {
                return $card !== null && in_array($card->getColor(), $emptyColors);
            });
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card'    => 'choice',
                    'choices' => $cardChoices,
                ))->setRequired(true)
                ->setExtraDatas(array('supremacy' => true, 'civilization' => 'active', 'target' => 'influence'))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_ARCHIVE, array(
                    'age' => array(
                        'type'    => 'choice',
                        'choices' => array(1),
                    ),
                ))
                ->setRequired(true)
                ->setExtraDatas(array('supremacy' => true))
            );
        }
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_ARCHIVE, array(
                    'age' => array(
                        'type'    => 'choice',
                        'choices' => array(1),
                    ),
                ))
                ->setRequired(true);
        }

        return $actions;
    }

    public function monnaie(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $civ->clearRecycled();
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))
                ->setExtraDatas(array('repeat' => true))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_REPEAT)->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true)));
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_MONNAIE_2)->setRequired(true);
        }

        return $actions;
    }

    public function monnaie2(Civilization $civilization)
    {
        for ($i = 1; $i <= $civilization->countAgesRecycled(); $i++)
        {
            if (!$this->isGameOver())
            {
                $this->drawToHand($civilization, 2);
            }
        }
    }

    // AGE 3

    public function papier(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY, array(
                'color'     => array(
                    'type'    => 'choice',
                    'choices' => array(Card::COLOR_BLUE, Card::COLOR_GREEN),
                ),
                'direction' => array(
                    'type'    => 'choice',
                    'choices' => array(Stack::SPLAY_LEFT),
                ),
            ));
        }

        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_PAPIER_2)
                ->setRequired(true);
        }

        return $actions;
    }

    public function papier2(Civilization $civilization)
    {
        foreach ($civilization->getStacks() as $stack)
        {
            if ($stack->getSplay() === Stack::SPLAY_LEFT && !$this->isGameOver())
            {
                $this->drawToHand($civilization, 4);
            }
        }
    }

    public function boussole(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardActive',
                        'args'   => array(
                            'colors'         => array(Card::COLOR_BLUE, Card::COLOR_GREEN, Card::COLOR_PURPLE, Card::COLOR_YELLOW),
                            'havingResource' => Card::RESOURCE_TREE,
                        ),
                    )
                ))->setRequired(true)
                ->setExtraDatas(array(
                'supremacy'    => true,
                'civilization' => $civilization->getId(),
                'target'       => 'game',
            ));

            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardActiveCiv',
                        'args'   => array(
                            'civ'               => $civilization->getId(),
                            'notHavingResource' => Card::RESOURCE_TREE,
                        ),
                    )
                ))->setRequired(true)
                ->setExtraDatas(array(
                'supremacy'    => true,
                'civilization' => $civ->getId(),
                'target'       => 'game',
            ));
        }

        return $actions;
    }

    public function ingenierie(Civilization $civilization)
    {
        $actions  = array();
        $civs     = $this->getDominatedCivs($civilization, Card::RESOURCE_STONE);
        $coopCivs = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ)
        {
            foreach ($civ->getActiveCards() as $card)
            {
                if ($card->hasResource(Card::RESOURCE_STONE))
                {
                    $this->transfer($civ, $card, $civilization->getInfluence());
                }
            }
        }
        $this->bonusCoop = false;
        foreach ($coopCivs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY)
                ->setExtraDatas(array(
                'color'     => Card::COLOR_RED,
                'direction' => Stack::SPLAY_LEFT,
            ));
        }

        return $actions;
    }

    public function feodalisme(Civilization $civilization)
    {
        $actions  = array();
        $civs     = $this->getDominatedCivs($civilization, Card::RESOURCE_STONE);
        $coopCivs = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                        'args'   => array('havingResource' => Card::RESOURCE_STONE),
                    )
                ))->setRequired(true)
                ->setExtraDatas(array('supremacy' => true, 'civilization' => $civilization->getId(), 'target' => 'hand'));
        }

        foreach ($coopCivs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY, array(
                    'color' => array(
                        'type'    => 'choice',
                        'choices' => array(Card::COLOR_YELLOW, Card::COLOR_PURPLE),
                    ),
                ))
                ->setExtraDatas(array('direction' => Stack::SPLAY_LEFT));
        }

        return $actions;
    }

    public function traduction(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_ACCEPT)
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromInfluence',
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array('repeat' => true))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_REPEAT)
                    ->setRequired(true)
                    ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
            ));
        }

        return $actions;
    }

    public function machinerie(Civilization $civilization)
    {
        $actions  = array();
        $civs     = $this->getDominatedCivs($civilization, Card::RESOURCE_TREE);
        $coopCivs = $this->getUndominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            $yours = $civ->getHand()->getElements();
            $mine  = $civilization->getHigherCardsInHand();
            foreach ($yours as $card)
            {
                $this->transfer($civ, $card, $civilization->getHand());
            }
            foreach ($mine as $card)
            {
                $this->transfer($civilization, $card, $civ->getHand());
            }
        }
        foreach ($coopCivs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SCORE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                        'args'   => array('havingResource' => Card::RESOURCE_STONE),
                    ),
                ))->setRequired(true);
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY)
                ->setExtraDatas(array('color' => Card::COLOR_RED, 'direction' => Stack::SPLAY_LEFT));
        }

        return $actions;
    }

    public function education(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                'card' => array(
                    'type'   => 'callback',
                    'method' => 'cardFromInfluence',
                    'args'   => array('age' => 'highestAgeInInfluence'),
                ),
            ));
        }
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND)
                    ->setExtraDatas(array(
                        'age' => array(
                            'type'   => 'callback',
                            'method' => 'highestAgeInInfluence',
                            'args'   => array('add' => 2),
                        ),
                    ))->setRequired(true);
        }

        return $actions;
    }

    public function optique(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_PLACE, array(
                    'age' => 3,
                ))->setRequired(true);
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_OPTIQUE_2);
        }

        return $actions;
    }

    public function optique2(Civilization $civilization)
    {
        $actions = array();
        if ($civilization->getLastDrawn()->hasResource(Card::RESOURCE_CROWN))
        {
            $this->drawAndScore($civilization, 4);
        }
        else
        {
            $actions[] = $this->createAction($civilization->getPlayer(), self::ACTION_TRANSFER, array(
                'card'         => array(
                    'type'   => 'callback',
                    'method' => 'cardFromInfluence',
                ),
                'civilization' => array(
                    'type'   => 'callback',
                    'method' => 'civWithLessInfluenceThan',
                    'args'   => array($civilization->countInfluence()),
                ),
                'target'       => 'influence',
            ));
        }

        return $actions;
    }

    public function alchimie(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_STONE);
        foreach ($civs as $civ)
        {
            $civ->clearDrawn();
            for ($i = 1; $i <= intval($civ->countResources()[Card::RESOURCE_STONE] / 3); $i++)
            {
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND)
                    ->setRequired(true)
                    ->setExtraDatas(array('public' => true));
            }
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_ALCHIMIE_2)->setRequired(true);
        }

        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->setRequired(true);
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SCORE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->setRequired(true);
        }

        return $actions;
    }

    public function alchimie2(Civilization $civilization)
    {
        $actions = array();
        if (array_reduce($civilization->getDrawn(), function($carry, $card)
            {
                return $carry || $card->getColor() === Card::COLOR_RED;
            }, false))
        {
            for ($i = 1; $i <= count($civilization->getHand()->getElements()); $i++)
            {
                $actions[] = $this->createAction($civilization->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ));
            }
        }

        return $actions;
    }

    public function medecine(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_EXCHANGE_START)
                ->setRequired(true)
                ->setExtraDatas(array(
                'civilization1' => $civilization->getId(),
                'target1'       => 'influence',
                'civilization2' => $civ->getId(),
                'target2'       => 'influence',
            ));
            $actions[] = $this->createAction($civilization->getPlayer(), self::ACTION_EXCHANGE_1, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromInfluence',
                        'args'   => array(
                            'age' => 'lowestAgeInInfluence',
                        ),
                    ),
                ))->setRequired(true);
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_EXCHANGE_2, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromInfluence',
                        'args'   => array(
                            'age' => 'highestAgeInInfluence',
                        ),
                    ),
                ))->setRequired(true);
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_EXCHANGE_COMMIT)
                ->setRequired(true)
                ->setExtraDatas(array('supremacy' => true));
        }

        return $actions;
    }

    // AGE 4

    public function experimentation(Civilization $civilization)
    {
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $this->drawAndPlace($civ, 5);
        }
    }

    public function colonialisme(Civilization $civilization)
    {
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_FACTORY);
        foreach ($civs as $civ)
        {
            $card = $this->drawAndArchive($civ, 3);
            while (!$this->isGameOver() && $card->hasResource(Card::RESOURCE_CROWN))
                $card = $this->drawAndArchive($civ, 3);
        }
    }

    public function navigation(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromInfluence',
                        'args'   => array(
                            'age' => array(2, 3),
                        ),
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array(
                'supremacy'    => true,
                'civilization' => $civilization->getId(),
                'target'       => 'influence',
            ));
        }

        return $actions;
    }

    public function poudre(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_FACTORY);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'activeCard',
                        'args'   => array('havingResource' => Card::RESOURCE_STONE),
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array(
                'supremacy'    => true,
                'civilization' => $civilization->getId(),
                'target'       => 'influence',
            ));
        }

        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_FACTORY);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_POUDRE_2);
        }

        return $actions;
    }

    public function poudre2(Civilization $civilization)
    {
        if (array_key_exists('transfered', $this->activationDatas && count($this->activationDatas['transfered'] > 0)))
        {
            $this->drawAndScore($civilization, 2);
        }
    }

    public function invention(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY, array(
                    'color' => array(
                        'type'   => 'callback',
                        'method' => 'splayedLeft',
                    ),
                ))->setExtraDatas(array(
                    'direction' => Stack::SPLAY_RIGHT,
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_SCORE)
                    ->setRequired(true)
                    ->setExtraDatas(array(
                        self::ACTION_PARAM_NO_DECLINE => true,
                        'age'                         => 4,
            )));
        }

        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_INVENTION_2);
        }

        return $actions;
    }

    public function invention2(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            // domination ???
        }

        return $actions;
    }

    public function reforme(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            for ($i = 1; $i <= $civ->countResources()[Card::RESOURCE_TREE] / 2; $i++)
            {
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_ARCHIVE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ));
            }
        }
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY, array(
                    'color' => array(
                        'type' => 'choice',
                        'choices' => array(Card::COLOR_YELLOW, Card::COLOR_PURPLE),
                    ),
                ))->setExtraDatas(array('direction' => Stack::SPLAY_RIGHT));
        }

        return $actions;
    }

    public function imprimerie(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromInfluence',
                    ),
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND)
                    ->setRequired(true)
                    ->setExtraDatas(array(
                        self::ACTION_PARAM_NO_DECLINE => true,
                        'age'                         => array(
                            'type'   => 'callback',
                            'method' => 'ageActives',
                            'args'   => array(
                                'colors' => array(Card::COLOR_PURPLE),
                                'add'    => 2
                            ),
                        ),
                    ))
            );
        }
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY, array(
                    'color' => array(Card::COLOR_BLUE),
                ))->setExtraDatas(array('direction' => Stack::SPLAY_RIGHT));
        }

        return $actions;
    }

    public function perspective(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $recyclingAction = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                'type'   => 'callback',
                'method' => 'cardFromHand',
            ));
            for ($i = 1; $i <= $civ->countResources()[Card::RESOURCE_LAMP] / 2; $i++)
            {
                $recyclingAction->addChild($this->createAction($civ->getPlayer(), self::ACTION_SCORE, array(
                            'card' => array(
                                'type'   => 'callback',
                                'method' => 'cardFromHand',
                            ),
                        ))->setRequired(true)
                        ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true)));
            }
            $actions[] = $recyclingAction;
        }

        return $actions;
    }

    public function anatomie(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromInfluence',
                    )
                ))->setRequired(true)
                ->setExtraDatas(array('supremacy' => true))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardActive',
                        'args'   => array('age' => 'ageLastRecycled'),
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array('supremacy' => true, self::ACTION_PARAM_NO_DECLINE => true)));
        }

        return $actions;
    }

    public function droit_des_societes(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardActive',
                        'args'   => array('colors' => array(Card::COLOR_BLUE, Card::COLOR_GREEN, Card::COLOR_RED, Card::COLOR_YELLOW), 'havingResource' => Card::RESOURCE_CROWN)
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array(
                    'supremacy'    => true,
                    'civilization' => $civilization->getId(),
                    'target'       => 'game',
                ))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_PLACE)
                ->setRequired(true)
                ->setExtraDatas(array(
                    'supremacy'                   => true,
                    self::ACTION_PARAM_NO_DECLINE => true,
                    'age'                         => 4,
                ))
            );
        }
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY)
                ->setExtraDatas(array(
                'color'     => Card::COLOR_GREEN,
                'direction' => Stack::SPLAY_RIGHT,
            ));
        }

        return $actions;
    }

    // AGE 5

    public function chimie(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_FACTORY);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY)
                ->setExtraDatas(array(
                'color'     => Card::COLOR_BLUE,
                'direction' => Stack::SPLAY_RIGHT,
            ));
        }
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_SCORE)
                ->setRequired(true)
                ->setExtraDatas(array(
                'age' => array(
                    'type'   => 'callback',
                    'method' => 'currentAge',
                    'args'   => array('add' => 1),
                ),
            ));
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromInfluence',
                    ),
                ))->setRequired(true);
        }

        return $actions;
    }

    public function astronomie(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND)
                    ->setRequired(true)
                    ->setExtraDatas(array(
                        'age'    => 6,
                        'repeat' => true,
                    ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_PLACE)
                    ->setRequired(true)
                    ->setExtraDatas(array(
                        'card' => array(
                            'type'   => 'callback',
                            'method' => 'lastDrawn',
                            'args'   => array('colors' => array(Card::COLOR_BLUE, Card::COLOR_GREEN)),
                        ),
                    ))
                    ->addChild($this->createAction($civ->getPlayer(), self::ACTION_REPEAT)
                        ->setRequired(true)
                        ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                    )
            );
        }
        foreach ($civs as $civ)
        {
            // check domination...
        }

        return $actions;
    }

    public function statistiques(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            $n = (!$civ->getInfluence()->isEmpty() && $civ->getHand()->isEmpty() ? 2 : 1);
            for ($i = 1; $i <= $n; $i++)
            {
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                        'card' => array(
                            'type'   => 'callback',
                            'method' => 'cardFromInfluence',
                            'args'   => array(
                                'age' => 'highestAgeInInfluence',
                            ),
                        ),
                    ))->setRequired(true)
                    ->setExtraDatas(array(
                    'supremacy'    => true,
                    'civilization' => $civ->getId(),
                    'target'       => 'hand',
                ));
            }
        }
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY)
                ->setExtraDatas(array(
                'color'     => Card::COLOR_YELLOW,
                'direction' => Stack::SPLAY_RIGHT,
            ));
        }

        return $actions;
    }

    public function physique(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            for ($i = 1; $i <= 3; $i++)
            {
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND)
                    ->setRequired(true)
                    ->setExtraDatas(array('public' => true, 'age' => 6));
            }
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array(
                    'repeat'     => true,
                    'conditions' => array(array('threeLastDrawnOfDifferentColors' => true)),
                ))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_REPEAT)
                ->setRequired(true)
                ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
            );
        }

        return $actions;
    }

    public function compagnies_marchandes(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardActive',
                        'args'   => array(
                            'color'          => array(Card::COLOR_BLUE, Card::COLOR_GREEN, Card::COLOR_RED, Card::COLOR_YELLOW),
                            'havingResource' => Card::RESOURCE_LAMP,
                        ),
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array('supremacy' => true))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND)
                ->setRequired(true)
                ->setExtraDatas(array('age' => 5, self::ACTION_PARAM_NO_DECLINE => true))
            );
        }

        return $actions;
    }

    public function charbon(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_FACTORY);
        foreach ($civs as $civ)
        {
            $this->drawAndArchive($civ, 5);
        }

        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY)
                ->setExtraDatas(array(
                'color'     => Card::COLOR_RED,
                'direction' => Stack::SPLAY_RIGHT,
            ));
        }

        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SCORE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardActive',
                    ),
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_SCORE)
                    ->setRequired(true)
                    ->setExtraDatas(array(
                        self::ACTION_PARAM_NO_DECLINE => true,
                        'card'                        => array(
                            'type'   => 'callback',
                            'method' => 'cardActive',
                            'args'   => array(
                                'color' => 'colorLastScored',
                            ),
                        ),
                    ))
            );
        }

        return $actions;
    }

    public function le_code_des_pirates(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            for ($i = 1; $i <= 2; $i++)
            {
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                        'card' => array(
                            'type'   => 'callback',
                            'method' => 'cardFromInfluence',
                            'args'   => array(
                                'maxAge' => 4
                            ),
                        ),
                    ))->setRequired(true)
                    ->setExtraDatas(array(
                    'civilization' => $civilization,
                    'target'       => 'influence',
                    'supremacy'    => true
                ));
            }
        }

        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SCORE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardActive',
                        'args'   => array(
                            'lowestHavingResource' => Card::RESOURCE_CROWN,
                        ),
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array(
                'conditions' => array(array('transfered' => true)),
            ));
        }

        return $actions;
    }

    public function machine_a_vapeur(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_FACTORY);
        foreach ($civs as $civ)
        {
            for ($i = 1; $i <= 2; $i++)
            {
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_ARCHIVE, array(
                        'age' => 4,
                    ))->setRequired(true);
            }
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardUnder',
                        'args'   => array(
                            'color' => Card::COLOR_YELLOW,
                        ),
                    ),
                ))->setRequired(true);
        }

        return $actions;
    }

    public function theorie_de_la_mesure(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_SPLAY)
                    ->setExtraDatas(array(
                        'direction'                   => Stack::SPLAY_RIGHT,
                        self::ACTION_PARAM_NO_DECLINE => true,
                    ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND)
                        ->setRequired(true)
                        ->setExtraDatas(array(
                            'age' => array(
                                'type'   => 'callback',
                                'method' => 'countLastStackSplayed',
                            ),
                        ))
                )
            );
        }

        return $actions;
    }

    public function systeme_bancaire(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                        'card' => array(
                            'type'   => 'callback',
                            'method' => 'cardActive',
                            'args'   => array(
                                'color'          => array(Card::COLOR_BLUE, Card::COLOR_PURPLE, Card::COLOR_RED, Card::COLOR_YELLOW),
                                'havingResource' => Card::RESOURCE_FACTORY,
                            ),
                        ),
                    ))->setRequired(true)
                    ->setExtraDatas(array(
                        'supremacy'    => true,
                        'civilization' => $civilization->getId(),
                        'target'       => 'game',
                    ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_SCORE, array(
                        'age' => 5
                    ))->setRequired(true)
                    ->setExtraDatas(array(
                        self::ACTION_PARAM_NO_DECLINE => true
                    ))
            );
        }

        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY)
                ->setExtraDatas(array(
                'color'     => Card::COLOR_GREEN,
                'direction' => Stack::SPLAY_RIGHT,
            ));
        }

        return $actions;
    }

    // AGE 6

    public function encyclopedie(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $age       = $civ->getHighestAgeInInfluence();
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromInfluence',
                        'args'   => array('age' => $age),
                    ),
                ))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromInfluence',
                        'args'   => array('age' => $age),
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array(
                    self::ACTION_PARAM_NO_DECLINE => true,
                    'repeat'                      => true,
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_REPEAT))
                ->setRequired(true)
                ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
            );
        }

        return $actions;
    }

    public function machines_outils(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_FACTORY);
        foreach ($civs as $civ)
        {
            $this->drawAndScore($civ, $civ->getHighestAgeInInfluence());
        }

        return $actions;
    }

    public function industrialisation(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_FACTORY);
        foreach ($civs as $civ)
        {
            for ($i = 1; $i = $civ->countResources()[Card::RESOURCE_FACTORY] / 2; $i++)
            {
                $this->drawAndArchive($civ, 6);
            }
        }

        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY, array(
                    'color' => array(Card::COLOR_RED, Card::COLOR_PURPLE),
                ))->setExtraDatas(array('direction' => Stack::SPLAY_RIGHT));
        }

        return $actions;
    }

    public function theorie_de_l_atome(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY)
                ->setExtraDatas(array(
                'color'     => array(Card::COLOR_BLUE),
                'direction' => Stack::SPLAY_RIGHT
            ));
        }
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_PLACE)
                ->setRequired(true)
                ->setExtraDatas(array('age' => 7)
            );
        }

        return $actions;
    }

    public function conserves(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_FACTORY);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_ARCHIVE)
                ->setExtraDatas(array('age' => 6))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_CONSERVES_2))
                ->setRequired(true);
        }
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY)
                ->setExtraDatas(array(
                'color'     => Card::COLOR_YELLOW,
                'direction' => Stack::SPLAY_RIGHT,
            ));
        }

        return $actions;
    }

    public function conserves2(Civilization $civilization)
    {
        foreach ($civilization->getActiveCards() as $card)
        {
            if ($card->hasResource(Card::RESOURCE_FACTORY))
            {
                $this->score($civilization, $card);
            }
        }
    }

    public function emancipation(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_FACTORY);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->setRequired(true)
                ->setExtraDatas(array(
                    'civilization' => $civilization->getId(),
                    'target' => 'influence',
                ))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND)
                ->setRequired(true)
                ->setExtraDatas(array(
                    self::ACTION_PARAM_NO_DECLINE => true,
                    'age'                         => 6,
                ))
            );
        }
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_FACTORY);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY, array(
                    'color' => array(Card::COLOR_RED, Card::COLOR_PURPLE),
                ))->setExtraDatas(array(
                'direction' => Stack::SPLAY_RIGHT,
            ));
        }

        return $actions;
    }

    public function classification(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $civ->setCardShown(null);
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SHOW, array(
                        'card' => array(
                            'type'   => 'callback',
                            'method' => 'cardFromHand',
                        ),
                    ))->setRequired(true)
                    ->addChild($this->createAction($civ->getPlayer(), self::ACTION_CLASSIFICATION_2)
                        ->setRequired(true)
                        ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                    )->addChild($this->createAction($civ->getPlayer(), self::ACTION_PLACE, array(
                        'card' => array(
                            'type'   => 'callback',
                            'method' => 'cardFromHand',
                            'args'   => array(
                                'color' => 'colorShown',
                            ),
                        ),
                    ))->setRequired(true)
                    ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true, 'repeat' => true))
                    ->addChild($this->createAction($civ->getPlayer(), self::ACTION_REPEAT)
                        ->setRequired(true)
                        ->setExtraDatas(array(
                            self::ACTION_PARAM_NO_DECLINE => true,
                        ))
                    )
            );
        }

        return $actions;
    }

    public function classification2(Civilization $civilization)
    {
        foreach ($this->civilizations as $civ)
        {
            if ($civ !== $civilization)
            {
                foreach ($civ->getHand()->getElements() as $card)
                {
                    if ($civilization->getCardShown() !== null && $card->getColor() === $civilization->getCardShown())
                    {
                        $this->transfer($civ, $civilization->getHand());
                    }
                }
            }
        }
    }

    public function democratie(Civilization $civilization)
    {
        $actions = array();
        foreach ($this->civilizations as $civ)
        {
            $civ->clearRecycled();
        }
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->setExtraDatas(array(
                    'repeat' => true,
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_REPEAT)
                    ->setRequired(true)
                    ->setExtraDatas(array(
                        self::ACTION_PARAM_NO_DECLINE => true
                    ))
            );
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DEMOCRATIE_2)
                ->setRequired(true);
        }

        return $actions;
    }

    public function democratie2(Civilization $civilization)
    {
        if ($civilization > array_reduce($this->civilizations, function($carry, $civ) use ($civilization)
            {
                return max($carry, ($civ !== $civilization ? count($civ->getRecycled()) : 0));
            }, 0))
        {
            $this->drawAndScore($civilization, 8);
        }
    }

    public function systeme_metrique(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            if ($civ->getStack(Card::COLOR_GREEN)->getSplay() === Stack::SPLAY_RIGHT)
            {
                $actions[]  = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY, array(
                    'color' => array_filter($civ->getStacks(), function($color, $stack)
                        {
                            return count($stack->getElements() >= 2) && $color !== Card::COLOR_GREEN && $stack->getSplay() !== Stack::SPLAY_RIGHT;
                        }, ARRAY_FILTER_USE_BOTH),
                    'direction' => Stack::SPLAY_RIGHT,
                ));
            }
        }
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY)
                ->setExtraDatas(array(
                'color'     => Card::COLOR_GREEN,
                'direction' => Stack::SPLAY_RIGHT,
            ));
        }

        return $actions;
    }

    public function vaccination(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getDominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            $age       = $civ->getLowestAgeInInfluence();
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromInfluence',
                        'args'   => array(
                            'age' => $age,
                        )
                    )
                ))->setRequired(true)
                ->setExtraDatas(array(
                    'supremacy' => true,
                    'repeat'    => true,
                ))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_REPEAT)
                    ->setRequired(true)
                    ->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                )
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_PLACE)
                ->setRequired(true)
                ->setExtraDatas(array(
                    'age' => 6,
                    'supremacy' => true,
                    self::ACTION_PARAM_NO_DECLINE => true,
                ))
            );
        }
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_PLACE)
                ->setRequired(true)
                ->setExtraDatas(array(
                    'conditions' => array('recycled' => true),
                    'age' => 7,
                ));
        }

        return $actions;
    }

    // AGE 7
    
    public function chemin_de_fer(Civilization $civilization)
    {
        $actions = array();
        $civs    = $this->getUndominatedCivs($civilization, Card::RESOURCE_CLOCK);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                'card' => array(
                    'type' => 'callback',
                    'method' => 'cardFromHand',
                ),
            ))->setRequired(true)
                ->setExtraDatas(array(
                    'repeat' => true,
                ))
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_REPEAT)
                    ->setRequired(true)
                    ->setExtraDatas(array(
                        self::ACTION_PARAM_NO_DECLINE => true,
                    ))
                );
            for ($i = 1; $i <= 3; $i++){
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND)
                    ->setRequired(true)
                    ->setExtraDatas(array('age' => 6));
            }
        }
        
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY, array(
                'color' => array(
                    'type' => 'callback',
                    'method' => 'colorSplayedRight',
                )
            ))->setExtraDatas(array(
                'direction' => Stack::SPLAY_TOP,
            ));
        }

        return $actions;
    }
    
    public function publications(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_REARRANGE_STACK, array(
                'color' => array(
                    'type' => 'callback',
                    'method' => 'colorSplayable',
                ),
            ));
        }
        
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SPLAY, array(
                'color' => array(
                    'type' => 'choice',
                    'choices' => array(Card::COLOR_YELLOW, Card::COLOR_BLUE),
                )
            ))->setExtraDatas(array(
                'direction' => Stack::SPLAY_TOP,
            ));
        }
        
        return $actions;
    }

    public function evolution(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_LAMP);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_CHOOSE_OPTION, array(
                'choosenOption' => array(
                    'type' => 'choice',
                    'choices' => array('evolution1', 'evolution2'),
                ),
            ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_SCORE, array(
                        'age' => 8,
                    ))->setRequired(true)
                    ->setExtraDatas(array(
                        self::ACTION_PARAM_NO_DECLINE => true,
                        'conditions' => array(array('choosenOption' => 'evolution1')),
                    ))
                    ->addChild($this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                        'card' => array(
                            'type' => 'callback',
                            'method' => 'cardFromInfluence',
                        )
                    ))->setRequired(true))
                )
                ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                    'age' => array(
                        'type' => 'callback',
                        'method' => 'highestAgeInInfluence',
                        'args' => array(
                            'add' => 1,
                        ),
                    )
                ))->setRequired(true)
                    ->setExtraDatas(array(
                        self::ACTION_PARAM_NO_DECLINE => true,
                        'conditions' => array(array('choosenOption' => 'evolution2')),
                )))
            ;
        }
        
        return $actions;
    }
    
    public function explosifs(Civlization $civilization)
    {
        $actions = array();
        $civs = $this->getDominatedCivs($civilization, Card::RESOURCE_FACTORY);
        foreach ($civs as $civ)
        {
            for ($i = 1; $i <= 3; $i++)
            {
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->setExtraDatas(array(
                    'supremacy' => true,
                    'civilization' => $civilization,
                    'target' => 'hand',
                ))->setRequired(true)
                    ->addChild($this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                            'card' => array(
                                'type' => 'callback',
                                'method' => 'cardFromHand',
                            ),
                        ))->setExtraDatas(array(
                            self::ACTION_PARAM_NO_DECLINE => true,
                            'supremacy' => true,
                            'civilization' => $civilization,
                            'target' => 'hand',
                        ))->setRequired(true)
                        ->addChild($this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                                'card' => array(
                                    'type' => 'callback',
                                    'method' => 'cardFromHand',
                                ),
                            ))->setExtraDatas(array(
                                self::ACTION_PARAM_NO_DECLINE => true,
                                'supremacy' => true,
                                'civilization' => $civilization,
                                'target' => 'hand',
                        ))->setRequired(true)))
                    ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                            'age' => 7,
                        ))->setRequired(true)
                        ->setExtraDatas(array(
                            self::ACTION_PARAM_NO_DECLINE => true,
                            'supremacy' => true,
                            'conditions' => array(
                                array('handSize' => 0),
                            ),
                    )))
                ;
            }
        }
        
        return $actions;
    }
    
    public function eclairage(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            $civ->clearArchived();
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_ARCHIVE, array(
                'card' => array(
                    'type' => 'callback',
                    'method' => 'cardFromHand',
                ),
            ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_ARCHIVE, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                    ->addChild($this->createAction($civ->getPlayer(), self::ACTION_ARCHIVE, array(
                        'card' => array(
                            'type' => 'callback',
                            'method' => 'cardFromHand',
                        ),
                    ))->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                        ->addChild($this->createAction($civ->getPlayer(), self::ACTION_ECLAIRAGE_2)->setRequired(true))
                )
            );
        }
        
        return $actions;
    }
    
    public function eclairage2(Civilization $civilization)
    {
        for ($i = 1; $i <= $civilization->getArchivedAges(); $i++)
        {
            $this->drawAndScore($civilization, 7);
        }
    }
    
    public function electricite(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_FACTORY);
        foreach ($civs as $civ)
        {
            $civ->clearRecycled();
            $cards = array_filter($civ->getActiveCards(), function($card){ return $card->hasResource(Card::RESOURCE_FACTORY); });
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE_MANY)
                ->setRequired(true)
                ->setExtraDatas(array(
                    'cards' => $cards,
                ))->addChild($this->createAction($civ->getPlayer(), self::ACTION_ELECTRICITE_2)->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true)));
        }
        
        return $actions;
    }
    
    public function electricite2(Civilization $civilization)
    {
        for ($i = 1; $i <= count($civilization->getRecycled()); $i++)
        {
            $this->drawToHand($civilization, 8);
        }
    }
    
    public function sante_publique(Civilization $civilization)
    {
        $actions[] = array();
        $civs = $this->getDominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_EXCHANGE_START)
                ->setRequired(true)
                ->setExtraDatas(array(
                'civilization1' => $civ->getId(),
                'target1'       => 'hand',
                'civilization2' => $civilization->getId(),
                'target2'       => 'hand',
            ));
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_EXCHANGE_1, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                        'args'   => array(
                            'age' => 'highestAgeInHand',
                        ),
                    ),
                ))->setRequired(true);
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_EXCHANGE_1, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                        'args'   => array(
                            'age' => 'highestAgeInHand',
                        ),
                    ),
                ))->setRequired(true);
            $actions[] = $this->createAction($civilization->getPlayer(), self::ACTION_EXCHANGE_2, array(
                    'card' => array(
                        'type'   => 'callback',
                        'method' => 'cardFromHand',
                        'args'   => array(
                            'age' => 'lowestAgeInHand',
                        ),
                    ),
                ))->setRequired(true);
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_EXCHANGE_COMMIT)
                ->setRequired(true)
                ->setExtraDatas(array('supremacy' => true));
        }
        
        return $actions;
    }
    
    public function refrigeration(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getDominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            for ($i = 1; $i <= $civ->getHandSize() / 2; $i++)
            {
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromHand',
                    ),
                ))->setRequired(true);
            }
        }
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_TREE);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_SCORE, array(
                'card' => array(
                    'type' => 'callback',
                    'method' => 'cardFromHand',
                ),
            ));
        }
        
        return $actions;
    }
    
    public function bicyclette(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_BICYCLETTE_2);
        }
        
        return $actions;
    }
    
    public function bicyclette2(Civilization $civilization)
    {
        $this->exchangeSets($civilization, $civilization->getHand(), $civilization->getInfluence());
    }

    public function moteur_a_explosion(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getDominatedCivs($civilization, Card::RESOURCE_CROWN);
        foreach ($civs as $civ)
        {
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                'card' => array(
                    'type' => 'callback',
                    'method' => 'cardFromInfluence',
                ),
            ))->setRequired(true)
            ->setExtraDatas(array(
                'civilization' => $civilization->getId(),
                'target' => 'influence',
            ));
            $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_TRANSFER, array(
                'card' => array(
                    'type' => 'callback',
                    'method' => 'cardFromInfluence',
                ),
            ))->setRequired(true)
            ->setExtraDatas(array(
                'civilization' => $civilization->getId(),
                'target' => 'influence',
            ));
        }
        
        return $actions;
    }
    
    // AGE 8
    
    public function theorie_quantique(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_CLOCK);
        foreach ($civs as $civ)
        {
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
                )))->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                    ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_TO_HAND, array(
                        'age' => 10,
                    ))->setExtraDatas(array(self::ACTION_PARAM_NO_DECLINE => true))
                        ->addChild($this->createAction($civ->getPlayer(), self::ACTION_DRAW_AND_SCORE, array(
                            'age' => 10,
                        ))
                    )
                )
            );
        }
        
        return $actions;
    }
    
    public function fusees(Civilization $civilization)
    {
        $actions = array();
        $civs = $this->getUndominatedCivs($civilization, Card::RESOURCE_CLOCK);
        foreach ($civs as $civ)
        {
            for ($i = 1; $i <= $civ->countResources()[Card::RESOURCE_CLOCK]; $i++)
            {
                $actions[] = $this->createAction($civ->getPlayer(), self::ACTION_RECYCLE, array(
                    'card' => array(
                        'type' => 'callback',
                        'method' => 'cardFromOpponentInfluence',
                    ),
                ));
            }
        }
    }
    
    // AGE 9

    
    
    // AGE 10
}
