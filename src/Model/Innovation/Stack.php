<?php

namespace App\Model\Innovation;

class Stack extends Set
{
    const SPLAY_NONE = 0;
    const SPLAY_LEFT = 1;
    const SPLAY_RIGHT = 2;
    const SPLAY_TOP = 3;
    
    private $splay;
    
    public function __construct($name, MetaPlayerInterface $owner = null)
    {
        parent::__construct($name, $owner);
        $this->splay = self::SPLAY_NONE;
    }
    
    public function getSplay()
    {
        return $this->splay;
    }
    
    public function splay($direction)
    {
        $this->splay = $direction;
    }

    public function addOnTop($element)
    {
        array_unshift($this->elements, $element);
        $element->setContainer($this);
    }
    
    public function addAtBottom($element)
    {
        $this->elements[] = $element;
        $element->setContainer($this);
    }
    
    public function pickOnTop()
    {
        $element = array_shift($this->elements);
        $element->setContainer(null);
        if (count($this->elements) < 2) $this->splay(self::SPLAY_NONE);
        
        return $element;
    }
    
    public function pickAtBottom()
    {
        $element = array_pop($this->elements);
        $element->setContainer(null);
        if (count($this->elements) < 2) $this->splay(self::SPLAY_NONE);
        
        return $element;
    }
    
    public function getTopElement()
    {
        return count($this->elements) > 0 ? $this->elements[min(array_keys($this->elements))] : null;
    }
    
    public function getBottomElement()
    {
        return count($this->elements) > 0 ? $this->elements[max(array_keys($this->elements))] : null;
    }
    
    /**
     * Element at rank i goes to rank $arrangement[i]
     * 
     * @param array $arrangement
     * @throws \Exception
     */
    public function rearrange($arrangement)
    {
        $ranks = array_keys($this->elements);
        if (count(array_filter($arrangement, function($value, $key) use($ranks){
            return !in_array($value, $ranks) || !in_array($key, $ranks);
        }, ARRAY_FILTER_USE_BOTH)) > 0)
        throw new \Exception('Unable to rearrange elements.');
        
        $this->elements = array_map(function($rank){
            return $this->elements[$rank];
        }, array_flip($arrangement));
        ksort($this->elements);
    }
    
    public function countResources()
    {
        $resources = array();
        $activeCard = $this->getTopElement();
        foreach ($this->elements as $card)
        {
            if ($card === $activeCard)
            {
                $resources[] = $card->getResources()[Card::SLOT_TOP_LEFT];
                $resources[] = $card->getResources()[Card::SLOT_BOTTOM_LEFT];
                $resources[] = $card->getResources()[Card::SLOT_BOTTOM_MIDDLE];
                $resources[] = $card->getResources()[Card::SLOT_BOTTOM_RIGHT];
            }
            elseif ($this->splay === self::SPLAY_LEFT)
            {
                $resources[] = $card->getResources()[Card::SLOT_BOTTOM_RIGHT];
            }
            elseif ($this->splay === self::SPLAY_RIGHT)
            {
                $resources[] = $card->getResources()[Card::SLOT_TOP_LEFT];
                $resources[] = $card->getResources()[Card::SLOT_BOTTOM_LEFT];
            }
            elseif ($this->splay === self::SPLAY_TOP)
            {
                $resources[] = $card->getResources()[Card::SLOT_BOTTOM_LEFT];
                $resources[] = $card->getResources()[Card::SLOT_BOTTOM_MIDDLE];
                $resources[] = $card->getResources()[Card::SLOT_BOTTOM_RIGHT];
            }
        }
        
        $result = array_count_values($resources);
        return array(
            Card::RESOURCE_STONE => $result[Card::RESOURCE_STONE] ?? 0,
            Card::RESOURCE_TREE => $result[Card::RESOURCE_TREE] ?? 0,
            Card::RESOURCE_CROWN => $result[Card::RESOURCE_CROWN] ?? 0,
            Card::RESOURCE_LAMP => $result[Card::RESOURCE_LAMP] ?? 0,
            Card::RESOURCE_FACTORY => $result[Card::RESOURCE_FACTORY] ?? 0,
            Card::RESOURCE_CLOCK => $result[Card::RESOURCE_CLOCK] ?? 0,
            Card::RESOURCE_AGE => $result[Card::RESOURCE_AGE] ?? 0,
        );
    }
}
