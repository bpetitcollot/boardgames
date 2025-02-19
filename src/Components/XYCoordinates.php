<?php

namespace App\Components;

/**
 * A simple cartesian coordinate representation to be used with any 2D grid
 */
class XYCoordinates
{
    public int $x;
    public int $y;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }
}