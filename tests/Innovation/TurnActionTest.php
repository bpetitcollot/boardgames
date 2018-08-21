<?php

namespace App\Tests\Innovation;

use App\Model\Innovation;
use App\Model\Innovation\Card;
use PHPUnit\Framework\TestCase;

class TurnActionTest extends TestCase
{

    public function testDraw()
    {
        // init game and state
        $rules = new Innovation();
        $game = StateTest::createGame();
        $rules->startGame($game);
        $state = $rules->getCurrentState($game);
        
        // create and handle action
        $civilization = $state->getCivilizations()[0];
        $action = $state->createAction($civilization->getPlayer(), 'draw');
        $rules->handleAction($game, $action, $state);
        
        return $this->assertEquals(3, count($state->getCivilizationHand($civilization)));
    }
    
    public function testPlace()
    {
        // init game and state
        $rules = new Innovation();
        $game = StateTest::createGame();
        $rules->startGame($game);
        $state = $rules->getCurrentState($game);
        
        // create and handle action
        $civilization = $state->getCivilizations()[0];
        $reforme = $state->getCard('reforme');
        $civilization->getHand()->add($reforme);
        $action = $state->createAction($civilization->getPlayer(), 'place')->setParams(array('card' => 'reforme'));
        $rules->handleAction($game, $action, $state);
        
        return $this->assertEquals('reforme', $civilization->getActiveCard(Card::COLOR_PURPLE)->getName());
    }
    
    public function testDominate()
    {
        
    }
    
    public function testActivate()
    {
        
    }
    
}
