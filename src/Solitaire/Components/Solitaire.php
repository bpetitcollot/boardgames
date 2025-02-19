<?php

namespace App\Solitaire\Components;

use App\Solitaire\Actions\Move;

class Solitaire
{
    const UNUSED = 0;
    const EMPTY = 1;
    const PAWN = 2;

    private array $grid;
    private array $actions = [];

    public function getGrid(): array
    {
        return $this->grid;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * The game starts with the britannic version of the game :
     *         x x x
     *         x x x
     *         x x x
     *   x x x x x x x x x
     *   x x x x O x x x x
     *   x x x x x x x x x
     *         x x x
     *         x x x
     *         x x x
     *
     *  x : pawn
     *  O : empty slot
     */
    public function __construct()
    {
        $threePawnsRow = [self::UNUSED, self::UNUSED, self::UNUSED, self::PAWN, self::PAWN, self::PAWN, self::UNUSED, self::UNUSED, self::UNUSED];
        $ninePawnsRow = [self::PAWN, self::PAWN, self::PAWN, self::PAWN, self::PAWN, self::PAWN, self::PAWN, self::PAWN, self::PAWN];
        $eightPawnsRow = [self::PAWN, self::PAWN, self::PAWN, self::PAWN, self::EMPTY, self::PAWN, self::PAWN, self::PAWN, self::PAWN];
        $this->grid = [
            $threePawnsRow,
            $threePawnsRow,
            $threePawnsRow,
            $ninePawnsRow,
            $eightPawnsRow,
            $ninePawnsRow,
            $threePawnsRow,
            $threePawnsRow,
            $threePawnsRow,
        ];
    }

    public function applyMove(Move $move): void
    {
        // TODO
    }

    public function isFinished(): bool
    {
        //TODO
        return false;
    }
}
