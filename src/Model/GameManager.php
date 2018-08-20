<?php

namespace App\Model;

use App\Entity\Game;
use App\Entity\Player;
use Symfony\Component\Security\Core\User\UserInterface;

class GameManager
{
    public function createGame($boardgame)
    {
        $game = new Game();
        $game->setBoardgame($boardgame);
        
        return $game;
    }
    
    public function initPlayers(Game $game, $playerNumber)
    {
        for ($i = 1; $i <= $playerNumber; $i++)
        {
            $player = new Player();
            $player->setColor(Player::COLORS[$i]);
            $game->addPlayer($player);
        }
    }
    
    public function joinGame(Game $game, UserInterface $user)
    {
        $player = null;
        foreach ($game->getPlayers() as $player)
        {
            if ($player->canJoin())
            {
                $player->setUser($user);
                break;
            }
        }
    }
    
    public function leaveGame(Game $game, UserInterface $user)
    {
        $player = null;
        foreach ($game->getPlayers() as $player)
        {
            if ($player->getUser() === $user)
            {
                $player->setUser(null);
                break;
            }
        }
    }
    
    public function startGame(Game $game)
    {
        
    }
    
    public function getCurrentState(Game $game)
    {
        
    }
    
    
}
