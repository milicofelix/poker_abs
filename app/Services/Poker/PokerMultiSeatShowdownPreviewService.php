<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;

final class PokerMultiSeatShowdownPreviewService
{
    /**
     * @param array<int, array{name?: string, rank?: int, kickers?: array<int, int>}> $handsBySeat
     * @return array<string, mixed>
     */
    public function preview(PokerTable $table, array $handsBySeat = []): array
    {
        $contenders = $this->contenders($table, $handsBySeat);
        $ranking = $this->rankContenders($contenders);
        $canResolveShowdown = count($contenders) >= 2 && $this->allContendersHaveHand($contenders);
        $winnerSeats = $canResolveShowdown ? $this->winnerSeats($ranking) : [];

        return [
            'phase' => '10.7',
            'schemaVersion' => 'multi_seat_showdown.v0',
            'enabledInMainEngine' => false,
            'activeContenders' => count($contenders),
            'canResolveShowdown' => $canResolveShowdown,
            'winnerSeats' => $winnerSeats,
            'isSplitPot' => count($winnerSeats) > 1,
            'ranking' => $ranking,
            'note' => 'Preview de showdown 3+ preparado. O motor principal heads-up continua sendo o responsável por finalizar mãos reais nesta fase.',
        ];
    }

    /**
     * @param array<int, array{name?: string, rank?: int, kickers?: array<int, int>}> $handsBySeat
     * @return array<int, array<string, mixed>>
     */
    private function contenders(PokerTable $table, array $handsBySeat): array
    {
        return $table->realPlayers()
            ->whereNull('left_at')
            ->orderByRaw('COALESCE(seat_number, 999999) asc')
            ->orderBy('id')
            ->get()
            ->filter(static fn (PokerTablePlayer $player): bool => ! in_array($player->status, ['folded', 'left'], true))
            ->values()
            ->map(function (PokerTablePlayer $player, int $index) use ($handsBySeat): array {
                $seatNumber = (int) ($player->seat_number ?? ($index + 1));
                $hand = $this->normalizeHand($handsBySeat[$seatNumber] ?? null);

                return [
                    'tablePlayerId' => $player->id,
                    'userId' => $player->user_id,
                    'nickname' => $player->nickname,
                    'seatNumber' => $seatNumber,
                    'isBot' => (bool) $player->is_bot,
                    'status' => $player->status,
                    'hand' => $hand,
                    'hasHand' => $hand !== null,
                ];
            })
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $contenders
     * @return array<int, array<string, mixed>>
     */
    private function rankContenders(array $contenders): array
    {
        usort($contenders, fn (array $left, array $right): int => $this->compareContenders($right, $left));

        return array_values(array_map(static function (array $contender, int $index): array {
            return [
                'position' => $index + 1,
                'seatNumber' => $contender['seatNumber'],
                'nickname' => $contender['nickname'],
                'isBot' => $contender['isBot'],
                'hasHand' => $contender['hasHand'],
                'handName' => $contender['hand']['name'] ?? null,
                'handRank' => $contender['hand']['rank'] ?? null,
                'kickers' => $contender['hand']['kickers'] ?? [],
            ];
        }, $contenders, array_keys($contenders)));
    }

    /**
     * @param array<int, array<string, mixed>> $ranking
     * @return array<int, int>
     */
    private function winnerSeats(array $ranking): array
    {
        if ($ranking === [] || ! ($ranking[0]['hasHand'] ?? false)) {
            return [];
        }

        $best = $ranking[0];

        return array_values(array_map(
            static fn (array $item): int => (int) $item['seatNumber'],
            array_filter($ranking, fn (array $item): bool => $this->sameHandStrength($best, $item)),
        ));
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    private function compareContenders(array $left, array $right): int
    {
        $leftHand = $left['hand'] ?? null;
        $rightHand = $right['hand'] ?? null;

        if ($leftHand === null && $rightHand === null) {
            return ((int) $left['seatNumber']) <=> ((int) $right['seatNumber']);
        }

        if ($leftHand === null) {
            return -1;
        }

        if ($rightHand === null) {
            return 1;
        }

        if ($leftHand['rank'] !== $rightHand['rank']) {
            return $leftHand['rank'] <=> $rightHand['rank'];
        }

        $totalKickers = max(count($leftHand['kickers']), count($rightHand['kickers']));

        for ($index = 0; $index < $totalKickers; $index++) {
            $leftKicker = $leftHand['kickers'][$index] ?? 0;
            $rightKicker = $rightHand['kickers'][$index] ?? 0;

            if ($leftKicker !== $rightKicker) {
                return $leftKicker <=> $rightKicker;
            }
        }

        return 0;
    }

    /**
     * @param mixed $hand
     * @return array{name: string, rank: int, kickers: array<int, int>}|null
     */
    private function normalizeHand(mixed $hand): ?array
    {
        if (! is_array($hand) || ! isset($hand['name'], $hand['rank'])) {
            return null;
        }

        $kickers = $hand['kickers'] ?? [];

        if (! is_array($kickers)) {
            $kickers = [];
        }

        return [
            'name' => (string) $hand['name'],
            'rank' => (int) $hand['rank'],
            'kickers' => array_map('intval', array_values($kickers)),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $contenders
     */
    private function allContendersHaveHand(array $contenders): bool
    {
        if ($contenders === []) {
            return false;
        }

        foreach ($contenders as $contender) {
            if (! ($contender['hasHand'] ?? false)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $best
     * @param array<string, mixed> $item
     */
    private function sameHandStrength(array $best, array $item): bool
    {
        if (! ($item['hasHand'] ?? false)) {
            return false;
        }

        return (int) ($best['handRank'] ?? 0) === (int) ($item['handRank'] ?? 0)
            && array_values($best['kickers'] ?? []) === array_values($item['kickers'] ?? []);
    }
}
