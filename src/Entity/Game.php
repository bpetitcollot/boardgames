<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Game
{
    private $id;
    private $boardgame;
    private $extensions;
    private $title;
    private $params;
    private $players;
    private $actionsRoot;
    private $state;

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->extensions = array();
    }
    
    public function getId()
    {
        return $this->id;
    }

    public function getBoardgame()
    {
        return $this->boardgame;
    }

    public function setBoardgame($boardgame)
    {
        $this->boardgame = $boardgame;
    }

    public function getExtensions()
    {
        return $this->extensions;
    }

    public function setExtensions($extensions)
    {
        $this->extensions = $extensions;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    public function getPlayers()
    {
        return $this->players;
    }
    
    public function addPlayer($player)
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
            $player->setGame($this);
        }
        return $this;
    }
    
    public function removePlayer($player)
    {
        if ($this->players->contains($player)) {
            $this->players->removeElement($player);
            $player->setGame(null);
        }
        return $this;
    }

    public function getActionsRoot()
    {
        return $this->actionsRoot;
    }

    public function setActionsRoot($actionsRoot)
    {
        $this->actionsRoot = $actionsRoot;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    public function playedBy(User $user)
    {
        return in_array($user, array_map(function($player){
            return $player->getUser();
        }, $this->players->toArray()));
    }
    
    public function isFull()
    {
        return !in_array(null, array_map(function($player){
            return $player->getUser();
        }, $this->players->toArray()));
    }
    
    public function isJoinable()
    {
        return !$this->isFull();
    }
    
    public function isStarted()
    {
        return $this->actionsRoot !== null;
    }
    
    public function getNextPlayer(Player $player = null)
    {
        return $player === null ? $this->players[0] : $this->players[(array_search($player, $this->players->toArray()) + 1) % count($this->players)];
    }
    
}
