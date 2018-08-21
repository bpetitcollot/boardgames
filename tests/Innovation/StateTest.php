<?php

namespace App\Tests\Innovation;

use App\Model\Innovation\State;
use App\Tests\TestPlayer;
use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{

    public function testInitGameOver()
    {
        $state = new State();
        $this->initState($state);
        return $this->assertEquals(false, $state->isGameOver());
    }
    
    public function testInitCivilizations()
    {
        $state = new State();
        $this->initState($state);
        return $this->assertEquals(3, count($state->getCivilizations()));
    }

    private function initState(State $state)
    {
        $players = array();
        foreach (array(1, 2, 3) as $id)
        {
            $players[] = new TestPlayer($id);
        }
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
        $shuffles = array(
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
        );
        $state->init($shuffles, $players);
    }
}
