<?php

namespace App\Services\Poker;

use App\Domain\Poker\Game\RoundStreet;

final class PokerBotProbabilityService
{
    /**
     * @param array<int, array<string, mixed>> $holeCards
     * @param array<int, array<string, mixed>> $communityCards
     * @return array{outs:int,potOdds:int,equity:int,evScore:int,callRecommendation:string,hasComboDraw:bool,outsLabel:string,potOddsLabel:string}
     */
    public function estimate(
        RoundStreet $street,
        array $holeCards,
        array $communityCards,
        int $pot = 0,
        int $amountToCall = 0,
        int $rank = 1,
        bool $hasFlushDraw = false,
        bool $hasStraightDraw = false,
    ): array {
        if ($street === RoundStreet::PreFlop) {
            return $this->emptyEstimate('pré-flop sem simulação probabilística');
        }

        $cardsToCome = match ($street) {
            RoundStreet::Flop => 2,
            RoundStreet::Turn => 1,
            default => 0,
        };

        $outs = $this->outs($holeCards, $communityCards, $hasFlushDraw, $hasStraightDraw, $rank);
        $drawEquity = $cardsToCome > 0 ? min(95, $outs * ($cardsToCome === 2 ? 4 : 2)) : 0;
        $madeHandEquity = $this->madeHandEquity($rank);
        $equity = max($drawEquity, $madeHandEquity);
        $potOdds = $this->potOdds($pot, $amountToCall);
        $evScore = $amountToCall <= 0 ? $equity : $equity - $potOdds;

        return [
            'outs' => $outs,
            'potOdds' => $potOdds,
            'equity' => $equity,
            'evScore' => $evScore,
            'callRecommendation' => $this->callRecommendation($evScore, $equity, $potOdds, $amountToCall),
            'hasComboDraw' => $hasFlushDraw && $hasStraightDraw,
            'outsLabel' => $this->outsLabel($outs, $hasFlushDraw, $hasStraightDraw),
            'potOddsLabel' => $amountToCall <= 0
                ? 'sem aposta para pagar'
                : sprintf('pot odds aproximadas de %d%%', $potOdds),
        ];
    }

    /**
     * @return array{outs:int,potOdds:int,equity:int,evScore:int,callRecommendation:string,hasComboDraw:bool,outsLabel:string,potOddsLabel:string}
     */
    private function emptyEstimate(string $label): array
    {
        return [
            'outs' => 0,
            'potOdds' => 0,
            'equity' => 0,
            'evScore' => 0,
            'callRecommendation' => 'neutral',
            'hasComboDraw' => false,
            'outsLabel' => $label,
            'potOddsLabel' => 'sem pot odds calculadas',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $holeCards
     * @param array<int, array<string, mixed>> $communityCards
     */
    private function outs(array $holeCards, array $communityCards, bool $hasFlushDraw, bool $hasStraightDraw, int $rank): int
    {
        $outs = 0;

        if ($hasFlushDraw) {
            $outs += 9;
        }

        if ($hasStraightDraw) {
            $outs += $this->hasOpenEndedStraightDraw(array_merge($holeCards, $communityCards)) ? 8 : 4;
        }

        if ($rank === 1 && count($holeCards) >= 2) {
            $outs += $this->overcardOuts($holeCards, $communityCards);
        }

        if ($rank === 2) {
            $outs += 5; // melhorar par para dois pares/trinca, de forma simplificada
        }

        if ($hasFlushDraw && $hasStraightDraw) {
            $outs -= 2; // cartas repetidas entre draws
        }

        return max(0, min(21, $outs));
    }

    private function madeHandEquity(int $rank): int
    {
        return match (true) {
            $rank >= 7 => 92,
            $rank === 6 => 86,
            $rank === 5 => 78,
            $rank === 4 => 68,
            $rank === 3 => 55,
            $rank === 2 => 38,
            default => 18,
        };
    }

    private function potOdds(int $pot, int $amountToCall): int
    {
        if ($amountToCall <= 0) {
            return 0;
        }

        return (int) round(($amountToCall / max(1, $pot + $amountToCall)) * 100);
    }

    private function callRecommendation(int $evScore, int $equity, int $potOdds, int $amountToCall): string
    {
        if ($amountToCall <= 0) {
            return $equity >= 55 ? 'value_bet' : 'check_allowed';
        }

        if ($evScore >= 14) {
            return 'profitable_call_or_raise';
        }

        if ($evScore >= 0) {
            return 'profitable_call';
        }

        if ($evScore >= -8) {
            return 'close_call';
        }

        return 'fold_by_odds';
    }

    /**
     * @param array<int, array<string, mixed>> $cards
     */
    private function hasOpenEndedStraightDraw(array $cards): bool
    {
        $values = array_values(array_unique(array_map(
            fn (array $card): int => $this->rankValue((string) ($card['rank'] ?? '2')),
            $cards,
        )));

        if (in_array(14, $values, true)) {
            $values[] = 1;
        }

        sort($values);

        for ($index = 0; $index <= count($values) - 4; $index++) {
            $window = array_slice($values, $index, 4);

            if (count($window) === 4 && max($window) - min($window) === 3) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $holeCards
     * @param array<int, array<string, mixed>> $communityCards
     */
    private function overcardOuts(array $holeCards, array $communityCards): int
    {
        if ($communityCards === []) {
            return 0;
        }

        $boardHigh = max(array_map(fn (array $card): int => $this->rankValue((string) ($card['rank'] ?? '2')), $communityCards));
        $outs = 0;

        foreach ($holeCards as $card) {
            if ($this->rankValue((string) ($card['rank'] ?? '2')) > $boardHigh) {
                $outs += 3;
            }
        }

        return min(6, $outs);
    }

    private function outsLabel(int $outs, bool $hasFlushDraw, bool $hasStraightDraw): string
    {
        if ($hasFlushDraw && $hasStraightDraw) {
            return sprintf('combo draw com %d outs aproximados', $outs);
        }

        if ($hasFlushDraw) {
            return sprintf('flush draw com %d outs aproximados', $outs);
        }

        if ($hasStraightDraw) {
            return sprintf('straight draw com %d outs aproximados', $outs);
        }

        return sprintf('%d outs aproximados', $outs);
    }

    private function rankValue(string $rank): int
    {
        return match ($rank) {
            'A' => 14,
            'K' => 13,
            'Q' => 12,
            'J' => 11,
            'T', '10' => 10,
            default => max(2, min(9, (int) $rank)),
        };
    }
}
