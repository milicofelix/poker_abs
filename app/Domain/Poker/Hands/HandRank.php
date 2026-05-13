<?php

namespace App\Domain\Poker\Hands;

enum HandRank: int
{
    case HighCard = 1;
    case Pair = 2;
    case TwoPair = 3;
    case ThreeOfAKind = 4;
    case Straight = 5;
    case Flush = 6;
    case FullHouse = 7;
    case FourOfAKind = 8;
    case StraightFlush = 9;

    public function label(): string
    {
        return match ($this) {
            self::HighCard => 'Carta alta',
            self::Pair => 'Par',
            self::TwoPair => 'Dois pares',
            self::ThreeOfAKind => 'Trinca',
            self::Straight => 'Sequência',
            self::Flush => 'Flush',
            self::FullHouse => 'Full house',
            self::FourOfAKind => 'Quadra',
            self::StraightFlush => 'Straight flush',
        };
    }
}
