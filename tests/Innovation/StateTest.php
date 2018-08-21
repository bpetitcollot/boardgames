<?php

namespace App\Tests\Innovation;

use App\Entity\Game;
use App\Model\Innovation;
use App\Model\Innovation\Card;
use App\Tests\TestPlayer;
use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{

    public static function createGame($countPlayers = 3, $shuffle = false)
    {
        $game = new Game();
        for ($id = 1; $id <= $countPlayers; $id++)
        {
            $player = new TestPlayer($id);
            $game->addPlayer($player);
        }
        $array1  = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];
        $array2  = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array3  = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array4  = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array5  = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array6  = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array7  = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array8  = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array9  = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $array10 = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        if ($shuffle)
        {
            for ($i = 1; $i <= 10; $i++)
            {
                shuffle(${'array' . $i});
            }
        }
        $shuffles = array(
            1  => $array1,
            2  => $array2,
            3  => $array3,
            4  => $array4,
            5  => $array5,
            6  => $array6,
            7  => $array7,
            8  => $array8,
            9  => $array9,
            10 => $array10,
        );
        $game->setParams(array('shuffles' => $shuffles));
        
        return $game;
    }

    public function testInitGameOver()
    {
        $rules = new Innovation();
        $game  = $this->createGame();
        $rules->startGame($game);
        $state = $rules->getCurrentState($game);
        return $this->assertEquals(false, $state->isGameOver());
    }

    public function testInitCivilizations()
    {
        $rules = new Innovation();
        $game  = $this->createGame();
        $rules->startGame($game);
        $state = $rules->getCurrentState($game);
        return $this->assertEquals(3, count($state->getCivilizations()));
    }

    public function testCountResourcesTree()
    {
        // init game and state
        $rules = new Innovation();
        $game = self::createGame();
        $rules->startGame($game);
        $state = $rules->getCurrentState($game);

        $civilization = $state->getCivilizations()[0];
        $reforme = $state->getCard('reforme');
        $civilization->place($reforme);
        
        return $this->assertEquals(3, $civilization->countResources()[Card::RESOURCE_TREE]);
    }

}
