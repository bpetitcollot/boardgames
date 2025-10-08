<?php

namespace App\Solitaire;

use App\Components\XYCoordinates;
use App\Entity\Boardgame;
use App\Entity\Game;
use App\Exception\UndeserializableActionException;
use App\Solitaire\Actions\Move;
use App\Solitaire\Components\Solitaire;

/**
 * Handle transformations from Game to Solitaire and backward
 *
 * Game rules are implemented in Solitaire class
 */
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
                $solitaire->applyMove($action);
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
        return $this->createGameStateFromSolitaire(null);
    }

    /**
     * How to store a Solitaire into a game state
     *
     * @param Solitaire|null $solitaire
     * @return array
     */
    public function createGameStateFromSolitaire(?Solitaire $solitaire): array
    {
        if ($solitaire === null){
            // initial state to be stored in a new Game instance
            return [
                'actions' => '',
                'isFinished' => false,
            ];
        }
        return [
            'actions' => $this->serializeActions($solitaire->getActions()),
            'isFinished' => $solitaire->isFinished(),
        ];
    }

    /**
     * Represent a move sequence in a string representation
     *
     * @param array $actions
     * @return string
     */
    public function serializeActions(array $actions): string
    {
        return implode('-', array_map(function (Move $action) {
            return implode(',', [$action->getStart()->x, $action->getStart()->y, $action->getEnd()->x, $action->getEnd()->y]);
        }, $actions));
    }

    /**
     * Extract a move sequence from a string representation
     *
     * @param string $string
     * @return array
     * @throws UndeserializableActionException
     */
    public function deserializeActions(string $string): array
    {
        if ($string === ''){
            return [];
        }
        return array_map(function(string $actionString) {
            return $this->deserializeAction($actionString);
        }, explode('-', $string));
    }

    /**
     * Create a Move from a string representation
     *
     * @param string $string
     * @return Move
     * @throws UndeserializableActionException
     */
    public function deserializeAction(string $string): Move
    {
        if (!preg_match('/^\d+,\d+,\d+,\d+$/', $string)){
            throw new UndeserializableActionException("'$string' cannot be deserialized to a Solitaire Move.");
        }
        $coordinates = explode(',', $string);
        return new Move(new XYCoordinates($coordinates[0], $coordinates[1]), new XYCoordinates($coordinates[2], $coordinates[3]));
    }
}