<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\Poker\PokerTournament;
use App\Models\Poker\PokerTournamentParticipant;
use App\Support\Poker\PokerBotProfiles;
use Illuminate\Support\Str;

final class PokerTournamentTableBridgeService
{
    public function __construct(
        private readonly PokerTableReadinessService $readiness,
        private readonly LocalPokerPersistenceService $persistence,
        private readonly MultiplayerPokerTableStateBroadcaster $broadcaster,
    ) {
    }

    public function ensureRuntimeTable(PokerTournament $tournament): PokerTable
    {
        /** @var PokerTournament $tournament */
        $tournament = $tournament->fresh(['participants.user', 'runtimeTable']);

        $table = $tournament->runtimeTable;

        if (! $table) {
            $table = PokerTable::query()->create([
                'name' => 'Mesa do torneio: '.$tournament->name,
                'status' => 'waiting',
                'small_blind' => max(1, (int) $tournament->small_blind),
                'big_blind' => max(2, (int) $tournament->big_blind),
                'buy_in' => max(1, (int) $tournament->starting_stack),
                'max_players' => max(2, min((int) $tournament->max_players, PokerTournament::FINAL_TABLE_MAX_PLAYERS)),
                'is_private' => false,
                'invite_code' => null,
            ]);

            $tournament->forceFill(['poker_table_id' => $table->id])->save();
        } else {
            $table->forceFill([
                'small_blind' => max(1, (int) $tournament->small_blind),
                'big_blind' => max(2, (int) $tournament->big_blind),
                'buy_in' => max(1, (int) $tournament->starting_stack),
                'max_players' => max(2, min((int) $tournament->max_players, PokerTournament::FINAL_TABLE_MAX_PLAYERS)),
            ])->save();
        }

        $this->syncParticipantsIntoSeats($tournament->fresh(['participants.user']), $table->fresh());
        $this->startFirstHandWhenReady($table->fresh());

        return $table->fresh(['realPlayers.user', 'hands']);
    }

    public function runtimePayloadFor(PokerTournament $tournament): array
    {
        $table = $tournament->runtimeTable;

        return [
            'phase' => '13.2.1',
            'enabled' => $table !== null,
            'tableId' => $table?->id,
            'tableName' => $table?->name,
            'tableUrl' => $table ? route('poker.tables.show', $table) : null,
            'status' => $table?->status,
            'smallBlind' => (int) ($table?->small_blind ?? $tournament->small_blind),
            'bigBlind' => (int) ($table?->big_blind ?? $tournament->big_blind),
            'playersSeated' => $table ? $table->realPlayers()->whereNotNull('seat_number')->whereNull('left_at')->count() : 0,
            'runningHands' => $table ? $table->hands()->where('status', 'running')->count() : 0,
            'latestHandId' => $table?->hands()->latest('id')->value('id'),
            'message' => $table
                ? 'Mesa real do torneio vinculada à engine atual de poker.'
                : 'A mesa real será criada automaticamente ao iniciar o torneio.',
        ];
    }

    private function syncParticipantsIntoSeats(PokerTournament $tournament, PokerTable $table): void
    {
        $activeParticipants = $tournament->participants
            ->filter(static fn (PokerTournamentParticipant $participant): bool => $participant->status === PokerTournamentParticipant::STATUS_ACTIVE)
            ->sortByDesc(static fn (PokerTournamentParticipant $participant): int => (int) $participant->current_stack)
            ->values();

        foreach ($activeParticipants as $index => $participant) {
            $seatNumber = $index + 1;
            $isBot = $this->participantLooksLikeBot($participant);

            PokerTablePlayer::query()->updateOrCreate(
                [
                    'poker_table_id' => $table->id,
                    'user_id' => $participant->user_id,
                ],
                [
                    'is_bot' => $isBot,
                    'bot_profile' => $isBot ? $this->botProfileForSeat($seatNumber) : null,
                    'bot_difficulty' => $isBot ? 'normal' : null,
                    'nickname' => $participant->user?->name ?? 'Jogador '.$seatNumber,
                    'stack' => max(0, (int) $participant->current_stack),
                    'buy_in_amount' => max(1, (int) $participant->starting_stack),
                    'buy_in_paid_at' => now(),
                    'seat_number' => $seatNumber,
                    'status' => 'online',
                    'joined_at' => now(),
                    'left_at' => null,
                    'last_seen_at' => now(),
                ],
            );
        }
    }

    private function startFirstHandWhenReady(PokerTable $table): void
    {
        if ($this->persistence->currentStateForTable($table) !== null) {
            return;
        }

        if (! $this->readiness->canStartHand($table)) {
            return;
        }

        $state = $this->readiness->startExplicitNewHand($table, $this->persistence, $this->isBotOnlyTable($table));
        $state['tournamentBridge'] = [
            'phase' => '13.2.1',
            'source' => 'tournament_runtime_table',
            'message' => 'Mão criada pela ponte Torneio → Engine Real.',
        ];

        $state = $this->persistence->persist($state);
        $this->broadcaster->broadcast($state);
    }

    private function participantLooksLikeBot(PokerTournamentParticipant $participant): bool
    {
        $email = (string) ($participant->user?->email ?? '');
        $name = (string) ($participant->user?->name ?? '');

        return Str::contains($email, '@pokerabs.local')
            || Str::contains($email, '@poker-abs.local')
            || Str::startsWith($name, 'Bot ');
    }

    private function botProfileForSeat(int $seatNumber): string
    {
        $profiles = PokerBotProfiles::keys();

        return $profiles[($seatNumber - 1) % max(1, count($profiles))] ?? 'balanced';
    }

    private function isBotOnlyTable(PokerTable $table): bool
    {
        $players = $table->realPlayers()
            ->whereNotNull('seat_number')
            ->whereNull('left_at')
            ->get();

        return $players->count() >= 2 && $players->every(static fn (PokerTablePlayer $player): bool => (bool) $player->is_bot);
    }
}
