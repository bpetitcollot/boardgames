<?php

namespace App\Solitaire\Components;

use App\Components\XYCoordinates;
use App\Solitaire\Actions\Move;
use App\Solitaire\Exception\InvalidSlotException;
use App\Solitaire\Exception\InvalidSlotValueException;

/**
 * Implements Solitaire game rules
 */
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

    /**
     * Whether the slot at specified coordinates contains a pawn, is an empty slot or is unused
     *
     * @param XYCoordinates $coordinates
     * @return int
     */
    public function getSlot(XYCoordinates $coordinates): int
    {
        if (!array_key_exists($coordinates->y, $this->grid) || !array_key_exists($coordinates->x, $this->grid[$coordinates->y])) {
            return self::UNUSED;
        }
        return $this->grid[$coordinates->y][$coordinates->x];
    }

    /**
     * Sets the slot at specified coordinates as a pawn, an empty slot or unused
     *
     * @param XYCoordinates $coordinates
     * @param int $value
     * @return void
     * @throws InvalidSlotException
     * @throws InvalidSlotValueException
     */
    public function updateSlot(XYCoordinates $coordinates, int $value): void
    {
        if (!array_key_exists($coordinates->y, $this->grid) || !array_key_exists($coordinates->x, $this->grid[$coordinates->y])) {
            throw new InvalidSlotException(sprintf('%d-%d is outside of the grid.', $coordinates->x, $coordinates->y));
        }
        if (!in_array($value, [self::UNUSED, self::EMPTY, self::PAWN])) {
            throw new InvalidSlotValueException("$value is not a valid slot value.");
        }
        $this->grid[$coordinates->y][$coordinates->x] = $value;
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

    /**
     * Check if the move is valid for the current grid
     *
     * @param Move $move
     * @return bool
     */
    public function validate(Move $move): bool
    {
        $jumped = new XYCoordinates(($move->getStart()->x + $move->getEnd()->x) / 2, ($move->getStart()->y + $move->getEnd()->y) / 2);
        return $this->getSlot($move->getStart()) === Solitaire::PAWN
            && $this->getSlot($jumped) === Solitaire::PAWN
            && $this->getSlot($move->getEnd()) === Solitaire::EMPTY
        ;
    }

    /**
     * Apply the move to the grid and add the move to the actions array
     *
     * @param Move $move
     * @return void
     * @throws InvalidSlotException
     * @throws InvalidSlotValueException
     */
    public function applyMove(Move $move): void
    {
        $jumped = new XYCoordinates(($move->getStart()->x + $move->getEnd()->x) / 2, ($move->getStart()->y + $move->getEnd()->y) / 2);
        $this->updateSlot($move->getStart(), self::EMPTY);
        $this->updateSlot($jumped, self::EMPTY);
        $this->updateSlot($move->getEnd(), self::PAWN);

        // grid stays up-to-date with actions
        $this->actions[] = $move;
    }

    /**
     * Check if the game is finished (e.g. no more move may be executed)
     *
     * @return bool
     */
    public function isFinished(): bool
    {
        // just for early return
        if (count($this->actions) === 43){
            return true;
        }
        foreach ($this->grid as $y => $row){
            foreach ($row as $x => $slot){
                if ($this->getSlot(new XYCoordinates($x, $y)) === Solitaire::PAWN){
                    foreach ([[0, 1], [0, -1], [1, 0], [-1, 0]] as [$dx, $dy]){
                        if ($this->getSlot(new XYCoordinates($x + $dx, $y + $dy)) === self::PAWN
                            && $this->getSlot(new XYCoordinates($x + 2 * $dx, $y + 2 * $dy)) === self::EMPTY
                        ){
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }
}
