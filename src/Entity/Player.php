<?php

namespace App\Entity;

class Player
{
    const COLORS = array('blue', 'green', 'red', 'purple', 'grey');
    
    protected $id;
    protected $game;
    protected $user;
    protected $color;
    
    public function getId()
    {
        return $this->id;
    }

    public function getGame()
    {
        return $this->game;
    }

    public function setGame($game)
    {
        $this->game = $game;
        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    public function canJoin()
    {
        return $this->user === null;
    }
    
    public function __toString()
    {
        return $this->getUser() ? $this->getUser()->getUsername() : '?';
    }
    
}
