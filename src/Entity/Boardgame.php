<?php

namespace App\Entity;

enum Boardgame: string
{
    case Solitaire = 'solitaire';
    case Checkers = 'checkers';
    case Innovation = 'innovation';
}
