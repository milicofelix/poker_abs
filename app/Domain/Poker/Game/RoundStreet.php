<?php

namespace App\Domain\Poker\Game;

enum RoundStreet: string
{
    case PreFlop = 'pre_flop';
    case Flop = 'flop';
    case Turn = 'turn';
    case River = 'river';
    case Showdown = 'showdown';

    public function label(): string
    {
        return match ($this) {
            self::PreFlop => 'Pré-flop',
            self::Flop => 'Flop',
            self::Turn => 'Turn',
            self::River => 'River',
            self::Showdown => 'Showdown',
        };
    }

    public function next(): self
    {
        return match ($this) {
            self::PreFlop => self::Flop,
            self::Flop => self::Turn,
            self::Turn => self::River,
            self::River, self::Showdown => self::Showdown,
        };
    }
}
