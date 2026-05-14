<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Support\Collection;

final class MultiplayerPokerPrivateStateService
{
    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function forUser(PokerTable $table, array $state, ?User $user): array
    {
        $realPlayers = $this->realPlayers($table);

        if (! $user) {
            return $this->withPlayersContext(
                $this->withoutPrivateOpponentData($state, 'spectator'),
                $realPlayers,
                null,
                'spectator',
            );
        }

        $currentPlayer = $realPlayers->first(
            static fn (PokerTablePlayer $player): bool => (int) $player->user_id === (int) $user->id,
        );

        if (! $currentPlayer) {
            return $this->withPlayersContext(
                $this->withoutPrivateOpponentData($state, 'spectator'),
                $realPlayers,
                null,
                'spectator',
            );
        }

        $position = $this->resolvePosition($realPlayers, $currentPlayer);

        $state = $position === 'opponent'
            ? $this->asOpponentPerspective($state, $currentPlayer)
            : $this->asPlayerPerspective($state, $currentPlayer);

        return $this->withPlayersContext($state, $realPlayers, $currentPlayer, $position);
    }

    /**
     * @return Collection<int, PokerTablePlayer>
     */
    private function realPlayers(PokerTable $table): Collection
    {
        return $table->realPlayers()
            ->orderByRaw('seat_number IS NULL')
            ->orderBy('seat_number')
            ->orderBy('joined_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param Collection<int, PokerTablePlayer> $players
     */
    private function resolvePosition(Collection $players, PokerTablePlayer $currentPlayer): string
    {
        if ((int) $currentPlayer->seat_number === 2) {
            return 'opponent';
        }

        if ((int) $currentPlayer->seat_number === 1) {
            return 'player';
        }

        $index = $players->values()->search(
            static fn (PokerTablePlayer $player): bool => (int) $player->id === (int) $currentPlayer->id,
        );

        return $index === 1 ? 'opponent' : 'player';
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function asPlayerPerspective(array $state, PokerTablePlayer $currentPlayer): array
    {
        $state = $this->withoutPrivateOpponentData($state, 'player');

        $state['multiplayerPerspective'] = [
            'role' => 'player',
            'tablePlayerId' => $currentPlayer->id,
            'userId' => $currentPlayer->user_id,
            'seatNumber' => $currentPlayer->seat_number,
            'label' => 'Suas cartas',
        ];

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function asOpponentPerspective(array $state, PokerTablePlayer $currentPlayer): array
    {
        $originalPlayerCards = $state['playerCards'] ?? [];
        $originalOpponentCards = $state['opponentCards'] ?? [];
        $originalBestHand = $state['bestHand'] ?? null;
        $originalOpponentBestHand = $state['opponentBestHand'] ?? null;

        $state['playerCards'] = $originalOpponentCards;
        $state['bestHand'] = $originalOpponentBestHand;

        if ((bool) ($state['isFinished'] ?? false)) {
            $state['opponentCards'] = $originalPlayerCards;
            $state['opponentBestHand'] = $originalBestHand;
        } else {
            unset($state['opponentCards'], $state['opponentBestHand']);
        }

        $state['multiplayerPerspective'] = [
            'role' => 'opponent',
            'tablePlayerId' => $currentPlayer->id,
            'userId' => $currentPlayer->user_id,
            'seatNumber' => $currentPlayer->seat_number,
            'label' => 'Suas cartas',
        ];

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function withoutPrivateOpponentData(array $state, string $role): array
    {
        if (! (bool) ($state['isFinished'] ?? false)) {
            unset($state['opponentCards'], $state['opponentBestHand']);
        }

        $state['multiplayerPerspective'] = [
            'role' => $role,
        ];

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @param Collection<int, PokerTablePlayer> $players
     * @return array<string, mixed>
     */
    private function withPlayersContext(array $state, Collection $players, ?PokerTablePlayer $currentPlayer, string $role): array
    {
        $playerOne = $players->first(static fn (PokerTablePlayer $player): bool => (int) $player->seat_number === 1)
            ?? $players->values()->get(0);
        $playerTwo = $players->first(static fn (PokerTablePlayer $player): bool => (int) $player->seat_number === 2)
            ?? $players->values()->get(1);

        $currentCanonicalActor = $role === 'opponent' ? 'opponent' : ($role === 'player' ? 'player' : null);

        $state['playersContext'] = [
            'current' => $currentPlayer ? $this->serializePlayer($currentPlayer, true) : null,
            'opponents' => $players
                ->reject(static fn (PokerTablePlayer $player): bool => $currentPlayer && (int) $player->id === (int) $currentPlayer->id)
                ->values()
                ->map(fn (PokerTablePlayer $player): array => $this->serializePlayer($player, false))
                ->all(),
            'canonical' => [
                'player' => $playerOne ? $this->serializePlayer($playerOne, $currentCanonicalActor === 'player') : null,
                'opponent' => $playerTwo ? $this->serializePlayer($playerTwo, $currentCanonicalActor === 'opponent') : null,
            ],
        ];

        $state['actionHistory'] = $this->personalizeHistory($state['actionHistory'] ?? [], $role, $state['playersContext']['canonical']);
        $state['conclusion'] = $this->personalizeConclusion($state['conclusion'] ?? null, $role, $state['playersContext']['canonical']);
        $state['lastAction'] = $this->personalizeLastAction($state['lastAction'] ?? null, $role, $state['playersContext']['canonical']);

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlayer(PokerTablePlayer $player, bool $isCurrent): array
    {
        return [
            'id' => $player->id,
            'userId' => $player->user_id,
            'nickname' => $player->nickname,
            'displayName' => $isCurrent ? 'Você' : ($player->nickname ?: 'Jogador'),
            'stack' => $player->stack,
            'status' => $player->status,
            'seatNumber' => $player->seat_number,
            'isCurrent' => $isCurrent,
        ];
    }

    /**
     * @param mixed $history
     * @param array<string, mixed> $canonicalPlayers
     * @return array<int, array<string, mixed>>
     */
    private function personalizeHistory(mixed $history, string $role, array $canonicalPlayers): array
    {
        if (! is_array($history)) {
            return [];
        }

        return array_values(array_map(
            function (mixed $item) use ($role, $canonicalPlayers): array {
                $item = is_array($item) ? $item : [];
                $canonicalActor = (string) ($item['actor'] ?? 'player');
                $visibleActor = $this->visibleActor($canonicalActor, $role);
                $actorLabel = $this->actorLabel($canonicalActor, $role, $canonicalPlayers);

                $item['canonicalActor'] = $canonicalActor;
                $item['actor'] = $visibleActor;
                $item['actorLabel'] = $actorLabel;
                $item['message'] = $this->personalizeText((string) ($item['message'] ?? ''), $role, $canonicalPlayers);

                return $item;
            },
            array_filter($history, static fn (mixed $item): bool => is_array($item)),
        ));
    }

    /**
     * @param mixed $conclusion
     * @param array<string, mixed> $canonicalPlayers
     * @return array<string, mixed>|null
     */
    private function personalizeConclusion(mixed $conclusion, string $role, array $canonicalPlayers): ?array
    {
        if (! is_array($conclusion)) {
            return null;
        }

        $winner = $conclusion['winner'] ?? null;

        if (is_array($winner)) {
            $canonicalWinner = (string) ($winner['player'] ?? '');

            if ($canonicalWinner !== 'tie') {
                $winner['canonicalPlayer'] = $canonicalWinner;
                $winner['player'] = $this->visibleActor($canonicalWinner, $role);
                $winner['label'] = $this->actorLabel($canonicalWinner, $role, $canonicalPlayers);
            }

            $conclusion['winner'] = $winner;
        }

        $conclusion['message'] = $this->personalizeText((string) ($conclusion['message'] ?? ''), $role, $canonicalPlayers);

        return $conclusion;
    }

    /**
     * @param mixed $lastAction
     * @param array<string, mixed> $canonicalPlayers
     * @return array<string, mixed>|null
     */
    private function personalizeLastAction(mixed $lastAction, string $role, array $canonicalPlayers): ?array
    {
        if (! is_array($lastAction)) {
            return null;
        }

        $lastAction['message'] = $this->personalizeText((string) ($lastAction['message'] ?? ''), $role, $canonicalPlayers);

        return $lastAction;
    }

    private function visibleActor(string $canonicalActor, string $role): string
    {
        if ($canonicalActor === 'tie') {
            return 'tie';
        }

        if ($role === 'opponent') {
            return $canonicalActor === 'opponent' ? 'player' : 'opponent';
        }

        return $canonicalActor === 'opponent' ? 'opponent' : 'player';
    }

    /**
     * @param array<string, mixed> $canonicalPlayers
     */
    private function actorLabel(string $canonicalActor, string $role, array $canonicalPlayers): string
    {
        if ($canonicalActor === 'tie') {
            return 'Empate';
        }

        $canonicalPlayer = $canonicalPlayers[$canonicalActor] ?? null;

        if ($role !== 'spectator' && $this->visibleActor($canonicalActor, $role) === 'player') {
            return 'Você';
        }

        return is_array($canonicalPlayer)
            ? (string) ($canonicalPlayer['nickname'] ?? $canonicalPlayer['displayName'] ?? 'Jogador')
            : ($canonicalActor === 'opponent' ? 'Oponente' : 'Jogador');
    }

    /**
     * @param array<string, mixed> $canonicalPlayers
     */
    private function personalizeText(string $text, string $role, array $canonicalPlayers): string
    {
        if ($text === '') {
            return $text;
        }

        $playerLabel = $this->actorLabel('player', $role, $canonicalPlayers);
        $opponentLabel = $this->actorLabel('opponent', $role, $canonicalPlayers);

        return str_replace(
            ['Você', 'você', 'O oponente', 'o oponente', 'Oponente'],
            [$playerLabel, mb_strtolower($playerLabel), $opponentLabel, mb_strtolower($opponentLabel), $opponentLabel],
            $text,
        );
    }
}
