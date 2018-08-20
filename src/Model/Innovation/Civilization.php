<?php

namespace App\Model\Innovation;

class Civilization implements MetaPlayerInterface
{

    private $player;
    private $hand;
    private $influence;
    private $dominations;
    private $projects;
    private $stacks;
    private $sufferSupremacy;
    private $archived;
    private $scored;
    private $recycled;
    
    public function __construct($player)
    {
        $this->player = $player;
        $this->hand = new Set('hand', $this);
        $this->influence = new Set('influence', $this);
        $this->dominations = new Set('dominations', $this);
        $this->projects = new Set('projects', $this);
        $this->stacks = array(
            Card::COLOR_RED => new Stack('red stack', $this),
            Card::COLOR_BLUE => new Stack('blue stack', $this),
            Card::COLOR_GREEN => new Stack('green stack', $this),
            Card::COLOR_YELLOW => new Stack('yellow stack', $this),
            Card::COLOR_PURPLE => new Stack('purple stack', $this),
        );
        $this->sufferSupremacy = false;
        $this->archived = array();
        $this->scored = array();
        $this->recycled = array();
    }

    public function getId()
    {
        return $this->player->getId();
    }
    
    public function getPlayer()
    {
        return $this->player;
    }

    public function getHand()
    {
        return $this->hand;
    }

    public function getInfluence()
    {
        return $this->influence;
    }

    public function getDominations()
    {
        return $this->dominations;
    }

    public function getProjects()
    {
        return $this->projects;
    }

    public function addToHand($card)
    {
        $this->hand->add($card);
    }

    public function addToInfluence($card)
    {
        $this->influence->add($card);
    }

    public function addToProjects($card)
    {
        $this->projects->add($card);
    }

    public function dominate($card)
    {
        $this->dominations->add($card);
    }

    public function getStacks()
    {
        return $this->stacks;
    }

    public function getStack($color)
    {
        return $this->stacks[$color];
    }

    public function getArchived()
    {
        return $this->archived;
    }

    public function getScored()
    {
        return $this->scored;
    }

    public function getRecycled()
    {
        return $this->recycled;
    }

    public function clearArchived()
    {
        $this->archived = array();
    }
    
    public function clearScored()
    {
        $this->scored = array();
    }
    
    public function clearRecycled()
    {
        $this->recycled = array();
    }
    
    public function sufferedSupremacy()
    {
        return $this->sufferSupremacy;
    }

    public function sufferSupremacy($sufferSupremacy)
    {
        $this->sufferSupremacy = $sufferSupremacy;
        return $this;
    }

    public function getAge()
    {
        return max(array_map(function($stack) {
                return $stack->getTopElement() !== null ? $stack->getTopElement()->getAge() : 1;
            }, $this->stacks));
    }

    public function countResources()
    {
        $count = array(
            Card::RESOURCE_STONE => 0,
            Card::RESOURCE_TREE => 0,
            Card::RESOURCE_CROWN => 0,
            Card::RESOURCE_LAMP => 0,
            Card::RESOURCE_FACTORY => 0,
            Card::RESOURCE_CLOCK => 0,
            Card::RESOURCE_AGE => 0,
        );
        
        return array_reduce($this->stacks, function($carry, $stack) {
            return array_map(function($c, $s){
                return $c + $s;
            }, $carry, $stack->countResources());
        }, $count);
    }
    
    public function countInfluence()
    {
        return array_reduce($this->influence->getElements(), function($carry, $card){
            return $carry + $card->getAge();
        }, 0);
    }
    
    public function getHigherCardsInHand()
    {
        $age = $this->getHighestAgeInHand();
        return array_filter($this->hand->getElements(), function($card) use ($age){
            return $card->getAge() === $age;
        });
    }

    public function getHighestAgeInHand()
    {
        return array_reduce($this->hand->getElements(), function($carry, $card){
            return max($carry, $card->getAge());
        }, 0);
    }
    
    public function getActiveCard($color)
    {
        return $this->stacks[$color]->getTopElement();
    }
    
    public function getActiveCards($includeEmpty = true)
    {
        $cards = array_map(function($stack){
            return $stack->getTopElement();
        }, $this->stacks);
        
        return $includeEmpty ? $cards : array_filter($cards, function($card){
            return $card !== null;
        });
    }
    
    public function getLastArchived()
    {
        $count = count($this->archived);
        return $count > 0 ? $this->archived[$count - 1] : null;
    }
    
    public function score(Card $card)
    {
        $this->addToInfluence($card);
        $this->scored[] = $card;
    }
    
    public function archive(Card $card)
    {
        $this->getStack($card->getColor())->addAtBottom($card);
        $this->archived[] = $card;
    }
    
    public function place(Card $card)
    {
        $this->getStack($card->getColor())->addOnTop($card);
    }
    
    public function recycle(Card $card)
    {
        $this->recycled[] = $card;
    }

    public function __toString()
    {
        return $this->player->__toString();
    }
    
}
