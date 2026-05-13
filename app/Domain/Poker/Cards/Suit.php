<?php

namespace App\Domain\Poker\Cards;

enum Suit: string
{
    case Clubs = 'clubs';
    case Diamonds = 'diamonds';
    case Hearts = 'hearts';
    case Spades = 'spades';
}