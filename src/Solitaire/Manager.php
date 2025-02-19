<?php

namespace App\Solitaire;

use App\Components\XYCoordinates;
use App\Entity\Boardgame;
use App\Entity\Game;
use App\Solitaire\Actions\Move;
use App\Solitaire\Components\Solitaire;

class Manager
{
    public function createSolitaireFromGame(Game $game): Solitaire
    {
        if ($game->getType() !== Boardgame::Solitaire){
            throw new \Exception("Game Type Not Supported");
        }

        $solitaire = new Solitaire();
        if (array_key_exists('actions', $game->getState())){
            $actions = $this->deserializeActions($game->getState()['actions']);
            foreach ($actions as $action){
                $this->addMove($solitaire, $action);
            }
        }

        return $solitaire;
    }

    /**
     * The initial state to be stored in a Game instance
     * @return array
     */
    public function createInitialState(): array
    {
        return ['actions' => ''];
    }

    public function addMove(Solitaire $solitaire, Move $move): void
    {
        $solitaire->applyMove($move);
    }

    public function serializeActions(array $actions): string
    {
        return implode('-', array_map(function (Move $action) {
            return implode(',', [$action->getStart()->x, $action->getStart()->y, $action->getEnd()->x, $action->getEnd()->y]);
        }, $actions));
    }
    public function deserializeActions(string $string): array
    {
        if ($string === ''){
            return [];
        }
        return array_map(function(string $actionString) {
            $coordinates = explode(',', $actionString);
            return new Move(new XYCoordinates($coordinates[0], $coordinates[1]), new XYCoordinates($coordinates[2], $coordinates[3]));
        }, explode('-', $string));
    }
}