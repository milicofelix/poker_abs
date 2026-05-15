<?php

namespace App\Services\Poker;

use App\Domain\Poker\Game\OpponentDecision;
use App\Domain\Poker\Game\PokerAction;
use App\Domain\Poker\Game\RoundStreet;
use App\Support\Poker\PokerBotProfiles;

final class PokerBotDecisionService
{
    /**
     * @param array{score?: int, label?: string, rank?: int, hasPairInHand?: bool, range?: string, drawBonus?: int, pressureBonus?: int, probability?: array<string, mixed>, outs?: int, equity?: int, potOdds?: int, evScore?: int, context?: array<string, mixed>, spr?: float|int, potPressure?: string, stackPressure?: string, opponentAggressionRate?: int, opponentFoldRate?: int, memory?: array<string, mixed>, memoryAdjustment?: int, bluffPressure?: int, callDownBias?: int} $handStrength
     */
    public function decide(
        string $profile,
        string $difficulty,
        RoundStreet $street,
        int $amountToCall,
        int $currentBet,
        int $botStack,
        array $handStrength = [],
    ): OpponentDecision {
        if ($botStack <= 0) {
            return new OpponentDecision(
                action: PokerAction::Check,
                amount: 0,
                message: 'Bot sem fichas disponíveis.',
            );
        }

        $settings = PokerBotProfiles::difficulty($difficulty);
        $score = max(0, min(100, (int) ($handStrength['score'] ?? 35) + (int) $settings['score_bonus']));
        $label = (string) ($handStrength['label'] ?? 'força indefinida');
        $range = (string) ($handStrength['range'] ?? 'unknown');
        $drawBonus = (int) ($handStrength['drawBonus'] ?? 0);
        $pressureBonus = (int) ($handStrength['pressureBonus'] ?? 0);
        $probability = is_array($handStrength['probability'] ?? null) ? $handStrength['probability'] : [];
        $context = is_array($handStrength['context'] ?? null) ? $handStrength['context'] : [];
        $memory = is_array($handStrength['memory'] ?? null) ? $handStrength['memory'] : (is_array($context['memory'] ?? null) ? $context['memory'] : []);
        $context = array_merge($context, [
            'spr' => $handStrength['spr'] ?? ($context['spr'] ?? null),
            'potPressure' => $handStrength['potPressure'] ?? ($context['potPressure'] ?? null),
            'stackPressure' => $handStrength['stackPressure'] ?? ($context['stackPressure'] ?? null),
            'opponentAggressionRate' => $handStrength['opponentAggressionRate'] ?? ($context['opponentAggressionRate'] ?? null),
            'opponentFoldRate' => $handStrength['opponentFoldRate'] ?? ($context['opponentFoldRate'] ?? null),
            'memory' => $memory,
        ]);

        if ($range === 'premium') {
            $score += 6;
        } elseif ($range === 'trash' && $street === RoundStreet::PreFlop) {
            $score -= 8;
        }

        $score = max(0, min(100, $score + (int) floor(($drawBonus + $pressureBonus) / 2)));
        $score = $this->applyProbabilityScore($score, $street, $amountToCall, $botStack, $probability, $profile);
        $score = $this->applyContextualScore($score, $street, $amountToCall, $botStack, (string) ($handStrength['boardTexture'] ?? 'unknown'), $context);
        $score = $this->applyMemoryScore($score, $street, $amountToCall, $profile, $context);
        $label = $this->memoryLabel($this->probabilityLabel($this->contextualLabel($label, $context), $probability), $context);

        return match ($profile) {
            'tag' => $this->tightAggressive($difficulty, $street, $amountToCall, $currentBet, $botStack, $score, $label, $settings),
            'lag' => $this->looseAggressive($difficulty, $street, $amountToCall, $currentBet, $botStack, $score, $label, $settings),
            'nit' => $this->nit($difficulty, $street, $amountToCall, $currentBet, $botStack, $score, $label, $settings),
            'calling_station' => $this->callingStation($difficulty, $street, $amountToCall, $currentBet, $botStack, $score, $label, $settings),
            'maniac' => $this->maniac($difficulty, $street, $amountToCall, $currentBet, $botStack, $score, $label, $settings),
            'aggressive' => $this->aggressive($difficulty, $street, $amountToCall, $currentBet, $botStack, $score, $label, $settings),
            default => $this->conservative($difficulty, $street, $amountToCall, $currentBet, $botStack, $score, $label, $settings),
        };
    }

    /**
     * @param array<string, int|string> $settings
     */
    private function conservative(
        string $difficulty,
        RoundStreet $street,
        int $amountToCall,
        int $currentBet,
        int $botStack,
        int $score,
        string $label,
        array $settings,
    ): OpponentDecision {
        $callLimit = match ($difficulty) {
            'hard' => 110,
            'normal' => 70,
            default => 40,
        } + (int) $settings['call_bias'];

        if ($street !== RoundStreet::PreFlop && $score >= 55) {
            $callLimit += 25;
        }

        $raiseThreshold = 82 + (int) $settings['raise_bias'];

        if ($street !== RoundStreet::PreFlop && $score >= 70) {
            $raiseThreshold -= 6;
        }

        if ($score >= $raiseThreshold && $botStack > $amountToCall + 40) {
            return new OpponentDecision(
                PokerAction::Raise,
                $this->raiseTarget($currentBet, $amountToCall, $botStack, $difficulty === 'hard' ? 60 : 40),
                $this->message('Bot conservador aumentou', $difficulty, $label),
            );
        }

        if ($amountToCall <= 0) {
            return new OpponentDecision(PokerAction::Check, 0, $this->message('Bot conservador pediu mesa', $difficulty, $label));
        }

        $adjustedLimit = max(10, $callLimit + (int) floor($score / 2));
        $stackPressureLimit = (int) floor($botStack * ($difficulty === 'hard' ? 0.18 : 0.12));
        $foldThreshold = 45 + (int) $settings['fold_bias'];

        if ($score < $foldThreshold && $amountToCall >= min($adjustedLimit, $stackPressureLimit)) {
            return new OpponentDecision(PokerAction::Fold, 0, $this->message('Bot conservador desistiu', $difficulty, $label));
        }

        return new OpponentDecision(
            PokerAction::Call,
            min($amountToCall, $botStack),
            $this->message('Bot conservador pagou', $difficulty, $label),
        );
    }

    /**
     * @param array<string, int|string> $settings
     */
    private function aggressive(
        string $difficulty,
        RoundStreet $street,
        int $amountToCall,
        int $currentBet,
        int $botStack,
        int $score,
        string $label,
        array $settings,
    ): OpponentDecision {
        $pressureLimit = match ($difficulty) {
            'hard' => 190,
            'normal' => 130,
            default => 80,
        } + (int) $settings['call_bias'];

        $raiseThreshold = 65 + (int) $settings['raise_bias'];
        $bluffThreshold = (int) $settings['bluff_score'];

        if ($street !== RoundStreet::PreFlop && $score >= 55) {
            $raiseThreshold -= 5;
            $bluffThreshold -= 6;
        }
        $canPressure = $botStack > $amountToCall + 60;

        if ($canPressure && ($score >= $raiseThreshold || ($street !== RoundStreet::PreFlop && $score >= $bluffThreshold))) {
            return new OpponentDecision(
                PokerAction::Raise,
                $this->raiseTarget($currentBet, $amountToCall, $botStack, $score >= 82 ? 90 : 50),
                $this->message('Bot agressivo aumentou pressionando', $difficulty, $label),
            );
        }

        if ($amountToCall <= 0) {
            return new OpponentDecision(PokerAction::Check, 0, $this->message('Bot agressivo aguardou', $difficulty, $label));
        }

        $maxPressureByStack = (int) floor($botStack * ($difficulty === 'hard' ? 0.36 : 0.30));

        if ($amountToCall <= min($pressureLimit + $score, $maxPressureByStack)) {
            return new OpponentDecision(
                PokerAction::Call,
                min($amountToCall, $botStack),
                $this->message('Bot agressivo pagou', $difficulty, $label),
            );
        }

        return new OpponentDecision(PokerAction::Fold, 0, $this->message('Bot agressivo desistiu', $difficulty, $label));
    }

    /**
     * @param array<string, int|string> $settings
     */
    private function tightAggressive(string $difficulty, RoundStreet $street, int $amountToCall, int $currentBet, int $botStack, int $score, string $label, array $settings): OpponentDecision
    {
        $score = max(0, min(100, $score + 4));
        $raiseThreshold = ($street === RoundStreet::PreFlop ? 70 : 64) + (int) $settings['raise_bias'];
        $canPressure = $botStack > $amountToCall + 50;

        if ($canPressure && $score >= $raiseThreshold) {
            return new OpponentDecision(
                PokerAction::Raise,
                $this->raiseTarget($currentBet, $amountToCall, $botStack, $score >= 82 ? 90 : 60),
                $this->message('Bot TAG aumentou com range protegido', $difficulty, $label),
            );
        }

        if ($amountToCall <= 0) {
            return new OpponentDecision(PokerAction::Check, 0, $this->message('Bot TAG controlou o pote', $difficulty, $label));
        }

        $callCap = (int) floor($botStack * ($difficulty === 'hard' ? 0.26 : 0.18));

        if ($score >= 48 && $amountToCall <= max(40, $callCap)) {
            return new OpponentDecision(PokerAction::Call, min($amountToCall, $botStack), $this->message('Bot TAG pagou seletivamente', $difficulty, $label));
        }

        return new OpponentDecision(PokerAction::Fold, 0, $this->message('Bot TAG largou spot marginal', $difficulty, $label));
    }

    /**
     * @param array<string, int|string> $settings
     */
    private function looseAggressive(string $difficulty, RoundStreet $street, int $amountToCall, int $currentBet, int $botStack, int $score, string $label, array $settings): OpponentDecision
    {
        $score = max(0, min(100, $score + ($street === RoundStreet::PreFlop ? 2 : 8)));
        $raiseThreshold = ($street === RoundStreet::PreFlop ? 60 : 52) + (int) $settings['raise_bias'];
        $canPressure = $botStack > $amountToCall + 50;

        if ($canPressure && $score >= $raiseThreshold) {
            return new OpponentDecision(
                PokerAction::Raise,
                $this->raiseTarget($currentBet, $amountToCall, $botStack, $score >= 76 ? 90 : 55),
                $this->message('Bot LAG pressionou com range amplo', $difficulty, $label),
            );
        }

        if ($amountToCall <= 0) {
            return new OpponentDecision(PokerAction::Check, 0, $this->message('Bot LAG aguardou para aplicar pressão', $difficulty, $label));
        }

        $callCap = (int) floor($botStack * ($difficulty === 'hard' ? 0.42 : 0.34));

        if ($amountToCall <= max(70, $callCap) || $score >= 42) {
            return new OpponentDecision(PokerAction::Call, min($amountToCall, $botStack), $this->message('Bot LAG pagou para manter pressão', $difficulty, $label));
        }

        return new OpponentDecision(PokerAction::Fold, 0, $this->message('Bot LAG desistiu do blefe', $difficulty, $label));
    }

    /**
     * @param array<string, int|string> $settings
     */
    private function nit(string $difficulty, RoundStreet $street, int $amountToCall, int $currentBet, int $botStack, int $score, string $label, array $settings): OpponentDecision
    {
        $score = max(0, min(100, $score - 8));
        $raiseThreshold = 92 + (int) floor(((int) $settings['raise_bias']) / 2);
        $canPressure = $botStack > $amountToCall + 60;

        if ($canPressure && $score >= $raiseThreshold) {
            return new OpponentDecision(
                PokerAction::Raise,
                $this->raiseTarget($currentBet, $amountToCall, $botStack, 70),
                $this->message('Bot Nit aumentou apenas com valor forte', $difficulty, $label),
            );
        }

        if ($amountToCall <= 0) {
            return new OpponentDecision(PokerAction::Check, 0, $this->message('Bot Nit pediu mesa com cautela', $difficulty, $label));
        }

        $callCap = (int) floor($botStack * ($difficulty === 'hard' ? 0.12 : 0.08));

        if ($score >= 62 && $amountToCall <= max(30, $callCap)) {
            return new OpponentDecision(PokerAction::Call, min($amountToCall, $botStack), $this->message('Bot Nit pagou com range forte', $difficulty, $label));
        }

        return new OpponentDecision(PokerAction::Fold, 0, $this->message('Bot Nit desistiu por disciplina', $difficulty, $label));
    }

    /**
     * @param array<string, int|string> $settings
     */
    private function callingStation(string $difficulty, RoundStreet $street, int $amountToCall, int $currentBet, int $botStack, int $score, string $label, array $settings): OpponentDecision
    {
        if ($amountToCall <= 0) {
            if ($score >= 95 && $botStack > 80) {
                return new OpponentDecision(
                    PokerAction::Raise,
                    $this->raiseTarget($currentBet, $amountToCall, $botStack, 40),
                    $this->message('Bot Calling Station aumentou com monstro', $difficulty, $label),
                );
            }

            return new OpponentDecision(PokerAction::Check, 0, $this->message('Bot Calling Station pediu mesa', $difficulty, $label));
        }

        $callCap = (int) floor($botStack * ($difficulty === 'hard' ? 0.45 : 0.36));

        if ($amountToCall <= max(90, $callCap) || $score >= 32) {
            return new OpponentDecision(PokerAction::Call, min($amountToCall, $botStack), $this->message('Bot Calling Station pagou curioso', $difficulty, $label));
        }

        return new OpponentDecision(PokerAction::Fold, 0, $this->message('Bot Calling Station finalmente largou', $difficulty, $label));
    }

    /**
     * @param array<string, int|string> $settings
     */
    private function maniac(string $difficulty, RoundStreet $street, int $amountToCall, int $currentBet, int $botStack, int $score, string $label, array $settings): OpponentDecision
    {
        $score = max(0, min(100, $score + ($street === RoundStreet::PreFlop ? 10 : 14)));
        $canPressure = $botStack > $amountToCall + 40;
        $raiseThreshold = ($street === RoundStreet::PreFlop ? 46 : 38) + (int) floor(((int) $settings['raise_bias']) / 2);

        if ($canPressure && $score >= $raiseThreshold) {
            return new OpponentDecision(
                PokerAction::Raise,
                $this->raiseTarget($currentBet, $amountToCall, $botStack, $score >= 70 ? 110 : 70),
                $this->message('Bot Maniac atacou sem medo', $difficulty, $label),
            );
        }

        if ($amountToCall <= 0) {
            return new OpponentDecision(PokerAction::Check, 0, $this->message('Bot Maniac segurou a agressão', $difficulty, $label));
        }

        $callCap = (int) floor($botStack * ($difficulty === 'hard' ? 0.50 : 0.40));

        if ($amountToCall <= max(80, $callCap)) {
            return new OpponentDecision(PokerAction::Call, min($amountToCall, $botStack), $this->message('Bot Maniac pagou para continuar atacando', $difficulty, $label));
        }

        return new OpponentDecision(PokerAction::Fold, 0, $this->message('Bot Maniac largou contra pressão extrema', $difficulty, $label));
    }


    /**
     * @param array<string, mixed> $probability
     */
    private function applyProbabilityScore(
        int $score,
        RoundStreet $street,
        int $amountToCall,
        int $botStack,
        array $probability,
        string $profile,
    ): int {
        if ($street === RoundStreet::PreFlop || $probability === []) {
            return max(0, min(100, $score));
        }

        $equity = (int) ($probability['equity'] ?? 0);
        $evScore = (int) ($probability['evScore'] ?? 0);
        $outs = (int) ($probability['outs'] ?? 0);
        $recommendation = (string) ($probability['callRecommendation'] ?? 'neutral');
        $isAggressiveProfile = in_array($profile, ['aggressive', 'lag', 'maniac', 'tag'], true);

        if ($recommendation === 'profitable_call_or_raise') {
            $score += $isAggressiveProfile ? 14 : 9;
        } elseif ($recommendation === 'profitable_call') {
            $score += 7;
        } elseif ($recommendation === 'close_call') {
            $score += in_array($profile, ['calling_station', 'maniac'], true) ? 5 : 1;
        } elseif ($recommendation === 'fold_by_odds') {
            $score -= in_array($profile, ['nit', 'conservative'], true) ? 12 : 7;
        }

        if ($amountToCall <= 0 && $outs >= 8 && $isAggressiveProfile && $botStack > 60) {
            $score += 8; // semi-bluff em draws fortes quando pode apostar sem pagar
        }

        if ($equity >= 70) {
            $score += 8;
        } elseif ($equity >= 50 && $evScore >= 0) {
            $score += 4;
        }

        return max(0, min(100, $score));
    }


    /**
     * @param array<string, mixed> $context
     */
    private function applyContextualScore(
        int $score,
        RoundStreet $street,
        int $amountToCall,
        int $botStack,
        string $boardTexture,
        array $context,
    ): int {
        if ($street === RoundStreet::PreFlop) {
            return max(0, min(100, $score));
        }

        $spr = is_numeric($context['spr'] ?? null) ? (float) $context['spr'] : null;
        $potPressure = (string) ($context['potPressure'] ?? 'normal_pot');
        $stackPressure = (string) ($context['stackPressure'] ?? 'comfortable');
        $opponentAggressionRate = (int) ($context['opponentAggressionRate'] ?? 0);
        $opponentFoldRate = (int) ($context['opponentFoldRate'] ?? 0);

        if ($potPressure === 'large_pot' && $score >= 58) {
            $score += 5;
        }

        if ($potPressure === 'large_pot' && $stackPressure === 'short_stack' && $score >= 48) {
            $score += 8;
        }

        if ($spr !== null && $spr <= 2.2 && $score >= 52) {
            $score += 6;
        }

        if ($spr !== null && $spr <= 1.2 && $potPressure === 'large_pot' && $score >= 48) {
            $score += 7;
        }

        if ($spr !== null && $spr >= 8 && $score < 55 && $amountToCall > 0) {
            $score -= 7;
        }

        if ($stackPressure === 'short_stack' && $score >= 50) {
            $score += 5;
        }

        if ($boardTexture === 'dangerous' && $score < 58 && $amountToCall > 0) {
            $score -= 6;
        }

        if ($opponentAggressionRate >= 60 && $score >= 48) {
            $score += 4;
        }

        if ($opponentFoldRate >= 50 && $botStack > $amountToCall + 60) {
            $score += 5;
        }

        return max(0, min(100, $score));
    }


    /**
     * @param array<string, mixed> $context
     */
    private function applyMemoryScore(int $score, RoundStreet $street, int $amountToCall, string $profile, array $context): int
    {
        $memory = is_array($context['memory'] ?? null) ? $context['memory'] : [];

        if ($memory === [] || (string) ($memory['opponentModel'] ?? 'unknown') === 'unknown') {
            return max(0, min(100, $score));
        }

        $score += (int) ($memory['scoreAdjustment'] ?? 0);
        $model = (string) ($memory['opponentModel'] ?? 'unknown');
        $bluffPressure = (int) ($memory['bluffPressure'] ?? 0);
        $callDownBias = (int) ($memory['callDownBias'] ?? 0);

        if ($amountToCall <= 0 && in_array($profile, ['aggressive', 'lag', 'maniac', 'tag'], true)) {
            $score += (int) floor($bluffPressure / 2);
        }

        if ($amountToCall > 0 && in_array($model, ['aggressive_opponent', 'polarized'], true)) {
            $score += (int) floor($callDownBias / 2);
        }

        if ($street !== RoundStreet::PreFlop && $model === 'calling_station_opponent' && $amountToCall <= 0) {
            $score += 4; // aposta por valor contra jogador que paga demais
        }

        return max(0, min(100, $score));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function contextualLabel(string $label, array $context): string
    {
        $parts = [];

        if (($context['potPressure'] ?? null) === 'large_pot') {
            $parts[] = 'pote grande';
        }

        if (($context['stackPressure'] ?? null) === 'short_stack') {
            $parts[] = 'stack curto';
        }

        if ((int) ($context['opponentAggressionRate'] ?? 0) >= 60) {
            $parts[] = 'vilão agressivo';
        }

        if ((int) ($context['opponentFoldRate'] ?? 0) >= 50) {
            $parts[] = 'vilão folda muito';
        }

        if ($parts === []) {
            return $label;
        }

        return sprintf('%s / contexto: %s', $label, implode(', ', $parts));
    }

    /**
     * @param array<string, mixed> $probability
     */
    private function probabilityLabel(string $label, array $probability): string
    {
        if ($probability === []) {
            return $label;
        }

        $parts = [];
        $recommendation = (string) ($probability['callRecommendation'] ?? 'neutral');

        if ((int) ($probability['outs'] ?? 0) >= 8) {
            $parts[] = (string) ($probability['outsLabel'] ?? 'bons outs');
        }

        if (in_array($recommendation, ['profitable_call_or_raise', 'profitable_call'], true)) {
            $parts[] = 'EV favorável';
        } elseif ($recommendation === 'fold_by_odds') {
            $parts[] = 'odds ruins';
        }

        if ($parts === []) {
            return $label;
        }

        return sprintf('%s / probabilidade: %s', $label, implode(', ', $parts));
    }


    /**
     * @param array<string, mixed> $context
     */
    private function memoryLabel(string $label, array $context): string
    {
        $memory = is_array($context['memory'] ?? null) ? $context['memory'] : [];
        $model = (string) ($memory['opponentModel'] ?? 'unknown');

        if ($memory === [] || $model === 'unknown') {
            return $label;
        }

        $memoryLabel = (string) ($memory['opponentModelLabel'] ?? 'adaptação ativa');

        return sprintf('%s / memória: %s', $label, $memoryLabel);
    }


    private function raiseTarget(int $currentBet, int $amountToCall, int $botStack, int $pressure): int
    {
        $target = max($currentBet + 20, $currentBet + $pressure);

        return min($target, $currentBet + $amountToCall + $botStack);
    }

    private function message(string $action, string $difficulty, string $label): string
    {
        return sprintf('%s (%s) com %s.', $action, PokerBotProfiles::difficultyLabel($difficulty), $label);
    }
}
