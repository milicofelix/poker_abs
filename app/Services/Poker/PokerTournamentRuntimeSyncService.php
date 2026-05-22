<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTablePlayer;
use App\Models\Poker\PokerTournament;
use App\Models\Poker\PokerTournamentParticipant;
use Illuminate\Support\Facades\DB;

final class PokerTournamentRuntimeSyncService
{
    public function __construct(
        private readonly PokerTournamentService $tournaments,
        private readonly PokerTournamentTableBridgeService $bridge,
    ) {
    }

    /**
     * Sincroniza a mesa real da engine com o torneio vinculado.
     *
     * A engine multi-seat continua sendo a fonte das regras. Este serviço apenas
     * espelha stacks, eliminações e metadados de runtime para o centro de torneios.
     *
     * @param array<string, mixed> $state
     */
    public function syncAfterStateChange(PokerTable $table, array $state): ?PokerTournament
    {
        $tournament = $this->runningTournamentForTable($table);

        if (! $tournament) {
            return null;
        }

        return DB::transaction(function () use ($table, $state, $tournament): PokerTournament {
            /** @var PokerTournament $lockedTournament */
            $lockedTournament = PokerTournament::query()
                ->with(['participants.user', 'runtimeTable'])
                ->whereKey($tournament->id)
                ->lockForUpdate()
                ->firstOrFail();

            $players = $this->statePlayers($state);
            $isFinished = (bool) ($state['isFinished'] ?? false);

            $this->syncTablePlayerStacks($players, $isFinished);
            $this->syncParticipantStacks($lockedTournament, $players, $isFinished);

            if ($isFinished) {
                $this->eliminateBustedParticipants($lockedTournament, $players);
                $this->advanceBlindLevelWhenDue($lockedTournament);
            }

            $freshTournament = $lockedTournament->fresh(['participants.user', 'runtimeTable']);

            if ($isFinished) {
                $this->normalizeFinishedHandSnapshot($table, $state, $freshTournament);
            }

            if ($isFinished && $freshTournament->status === PokerTournament::STATUS_RUNNING) {
                $this->bridge->prepareRuntimeTableForNextHand($freshTournament);
                $freshTournament = $freshTournament->fresh(['participants.user', 'runtimeTable']);
            }

            $this->tournaments->refreshResumeSnapshot($freshTournament);

            return $freshTournament;
        });
    }

    public function runningTournamentForTable(PokerTable $table): ?PokerTournament
    {
        /** @var PokerTournament|null $tournament */
        $tournament = PokerTournament::query()
            ->with(['participants.user', 'runtimeTable'])
            ->where('poker_table_id', $table->id)
            ->where('status', PokerTournament::STATUS_RUNNING)
            ->first();

        return $tournament;
    }

    /** @param array<string, mixed> $state */
    public function runtimePayload(PokerTable $table, array $state): ?array
    {
        $tournament = $this->runningTournamentForTable($table);

        if (! $tournament) {
            return null;
        }

        $players = $this->statePlayers($state);
        $activePlayers = $tournament->participants
            ->where('status', PokerTournamentParticipant::STATUS_ACTIVE)
            ->count();

        return [
            'phase' => '13.2.3',
            'tournamentId' => $tournament->id,
            'name' => $tournament->name,
            'status' => $tournament->status,
            'blindLevel' => max(1, (int) $tournament->current_blind_level),
            'smallBlind' => max(1, (int) $tournament->small_blind),
            'bigBlind' => max(2, (int) $tournament->big_blind),
            'nextBlindAt' => $tournament->next_blind_at?->toIso8601String(),
            'activePlayers' => $activePlayers,
            'isFinalTable' => (bool) $tournament->is_final_table,
            'syncedPlayers' => count($players),
            'isHandFinished' => (bool) ($state['isFinished'] ?? false),
            'canStartNextHand' => (bool) ($state['isFinished'] ?? false) && $tournament->status === PokerTournament::STATUS_RUNNING && $activePlayers >= 2,
            'message' => (bool) ($state['isFinished'] ?? false)
                ? 'Mão encerrada: torneio pronto para reencaixar jogadores ativos e iniciar a próxima mão real.'
                : 'Mesa real sincronizada com stacks, blinds e eliminações do torneio.',
        ];
    }


    /**
     * Mantém o snapshot visual da mão finalizada coerente com o torneio.
     *
     * Sem isso, a tela pode reidratar a última mão finalizada exibindo um
     * jogador com stack 0 como "Ativo", mesmo depois de o centro de torneios
     * já ter marcado esse participante como eliminado.
     *
     * @param array<string, mixed> $state
     */
    private function normalizeFinishedHandSnapshot(PokerTable $table, array $state, PokerTournament $tournament): void
    {
        /** @var PokerHand|null $hand */
        $hand = $table->hands()
            ->latest('id')
            ->first();

        if (! $hand || ! is_array($hand->state_payload)) {
            return;
        }

        $statusByUserId = $tournament->fresh(['participants'])
            ->participants
            ->mapWithKeys(static fn (PokerTournamentParticipant $participant): array => [
                (int) $participant->user_id => [
                    'status' => $participant->status,
                    'stack' => max(0, (int) $participant->current_stack),
                    'finishPosition' => $participant->finish_position,
                ],
            ])
            ->all();

        $payload = $hand->state_payload;
        $players = data_get($payload, 'multiSeat.players', []);

        if (! is_array($players)) {
            return;
        }

        foreach ($players as $index => $player) {
            if (! is_array($player)) {
                continue;
            }

            $userId = (int) ($player['userId'] ?? 0);
            $tournamentStatus = $statusByUserId[$userId] ?? null;

            if (! is_array($tournamentStatus)) {
                continue;
            }

            $players[$index]['stack'] = (int) $tournamentStatus['stack'];
            $players[$index]['tournamentStatus'] = (string) $tournamentStatus['status'];
            $players[$index]['finishPosition'] = $tournamentStatus['finishPosition'];

            if ($tournamentStatus['status'] !== PokerTournamentParticipant::STATUS_ACTIVE) {
                $players[$index]['status'] = 'eliminated';
                $players[$index]['hasFolded'] = true;
                $players[$index]['streetBet'] = 0;
            }
        }

        data_set($payload, 'multiSeat.players', $players);
        data_set($payload, 'tournamentRuntimeSnapshot.phase', '13.2.4');
        data_set($payload, 'tournamentRuntimeSnapshot.message', 'Snapshot final normalizado com eliminações reais do torneio.');

        $hand->forceFill(['state_payload' => $payload])->save();
    }

    /** @return array<int, array<string, mixed>> */
    private function statePlayers(array $state): array
    {
        $players = data_get($state, 'multiSeat.players', []);

        if (! is_array($players)) {
            return [];
        }

        return array_values(array_filter($players, static fn (mixed $player): bool => is_array($player)));
    }

    /**
     * @param array<int, array<string, mixed>> $players
     */
    private function syncTablePlayerStacks(array $players, bool $isFinished): void
    {
        foreach ($players as $player) {
            $tablePlayerId = (int) ($player['tablePlayerId'] ?? 0);

            if ($tablePlayerId <= 0) {
                continue;
            }

            PokerTablePlayer::query()
                ->whereKey($tablePlayerId)
                ->update([
                    'stack' => $this->runtimeStackFor($player, $isFinished),
                    'last_seen_at' => now(),
                ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $players
     */
    private function syncParticipantStacks(PokerTournament $tournament, array $players, bool $isFinished): void
    {
        $stackByUserId = [];

        foreach ($players as $player) {
            $userId = (int) ($player['userId'] ?? 0);

            if ($userId <= 0) {
                continue;
            }

            $stackByUserId[$userId] = $this->runtimeStackFor($player, $isFinished);
        }

        foreach ($stackByUserId as $userId => $stack) {
            PokerTournamentParticipant::query()
                ->where('poker_tournament_id', $tournament->id)
                ->where('user_id', $userId)
                ->where('status', PokerTournamentParticipant::STATUS_ACTIVE)
                ->update(['current_stack' => $stack]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $players
     */
    private function eliminateBustedParticipants(PokerTournament $tournament, array $players): void
    {
        $bustedUserIds = collect($players)
            ->filter(fn (array $player): bool => $this->runtimeStackFor($player, true) <= 0)
            ->map(static fn (array $player): int => (int) ($player['userId'] ?? 0))
            ->filter(static fn (int $userId): bool => $userId > 0)
            ->values();

        foreach ($bustedUserIds as $userId) {
            /** @var PokerTournamentParticipant|null $participant */
            $participant = PokerTournamentParticipant::query()
                ->where('poker_tournament_id', $tournament->id)
                ->where('user_id', $userId)
                ->where('status', PokerTournamentParticipant::STATUS_ACTIVE)
                ->first();

            if (! $participant) {
                continue;
            }

            $this->tournaments->eliminate($tournament->fresh(), $participant);
        }
    }

    private function advanceBlindLevelWhenDue(PokerTournament $tournament): void
    {
        /** @var PokerTournament|null $freshTournament */
        $freshTournament = PokerTournament::query()
            ->whereKey($tournament->id)
            ->first();

        if (! $freshTournament || $freshTournament->status !== PokerTournament::STATUS_RUNNING) {
            return;
        }

        if ($freshTournament->next_blind_at === null || $freshTournament->next_blind_at->isFuture()) {
            return;
        }

        $this->tournaments->advanceBlindLevel($freshTournament);
    }

    /** @param array<string, mixed> $player */
    private function runtimeStackFor(array $player, bool $isFinished): int
    {
        $stack = max(0, (int) ($player['stack'] ?? 0));

        if ($isFinished) {
            return $stack;
        }

        return $stack + max(0, (int) ($player['streetBet'] ?? 0));
    }
}
