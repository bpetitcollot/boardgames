<?php

namespace App\Solitaire\Actions;

use App\Components\XYCoordinates;

/**
 * This is the only action available for Solitaire.
 * A Pawn may move from $start coordinates to $end coordinates jumping above another pawn.
 */
class Move
{
    private XYCoordinates $start;
    private XYCoordinates $end;

    public function __construct(XYCoordinates $start, XYCoordinates $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function getStart(): XYCoordinates
    {
        return $this->start;
    }

    public function getEnd(): XYCoordinates
    {
        return $this->end;
    }

}