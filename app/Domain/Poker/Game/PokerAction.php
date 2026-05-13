<?php

namespace App\Domain\Poker\Game;

enum PokerAction: string
{
    case Check = 'check';
    case Call = 'call';
    case Raise = 'raise';
    case Fold = 'fold';

    public function label(): string
    {
        return match ($this) {
            self::Check => 'Check',
            self::Call => 'Call',
            self::Raise => 'Raise',
            self::Fold => 'Fold',
        };
    }
}
