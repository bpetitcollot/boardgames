<?php

namespace App\Tests;

use App\Entity\Player;

class TestPlayer extends Player
{
    public function __construct($id)
    {
        $this->id = $id;
    }
    
    public function __toString()
    {
        return 'player '.$this->id;
    }
}
