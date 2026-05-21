<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerBankrollTransaction;
use App\Models\Poker\PokerTournament;
use App\Models\Poker\PokerTournamentParticipant;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class PokerTournamentService
{
    public function indexPayload(?User $user = null): array
    {
        $tournaments = PokerTournament::query()
            ->with(['participants.user', 'runtimeTable'])
            ->withCount('participants')
            ->latest()
            ->get()
            ->map(fn (PokerTournament $tournament): array => $this->serializeTournament($tournament, $user))
            ->values()
            ->all();

        return [
            'phase' => '12.12.10',
            'summary' => [
                'total' => count($tournaments),
                'registering' => collect($tournaments)->where('status', PokerTournament::STATUS_REGISTERING)->count(),
                'running' => collect($tournaments)->where('status', PokerTournament::STATUS_RUNNING)->count(),
                'finished' => collect($tournaments)->where('status', PokerTournament::STATUS_FINISHED)->count(),
            ],
            'lobby' => $this->lobbyPayloadFor($tournaments),
            'defaults' => [
                'buyIn' => PokerTournament::DEFAULT_BUY_IN,
                'startingStack' => PokerTournament::DEFAULT_STARTING_STACK,
                'maxPlayers' => PokerTournament::DEFAULT_MAX_PLAYERS,
                'smallBlind' => PokerTournament::DEFAULT_SMALL_BLIND,
                'bigBlind' => PokerTournament::DEFAULT_BIG_BLIND,
                'blindLevelMinutes' => PokerTournament::DEFAULT_BLIND_LEVEL_MINUTES,
                'payoutStructure' => PokerTournament::DEFAULT_PAYOUT_STRUCTURE,
                'finalTableMaxPlayers' => PokerTournament::FINAL_TABLE_MAX_PLAYERS,
                'maxReentriesPerPlayer' => PokerTournament::DEFAULT_MAX_REENTRIES_PER_PLAYER,
                'addonAvailableUntilBlindLevel' => PokerTournament::DEFAULT_ADDON_AVAILABLE_UNTIL_BLIND_LEVEL,
            ],
            'tournaments' => $tournaments,
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): PokerTournament
    {
        /** @var PokerTournament $tournament */
        $tournament = PokerTournament::query()->create([
            'name' => trim((string) $data['name']),
            'status' => PokerTournament::STATUS_REGISTERING,
            'buy_in' => (int) ($data['buy_in'] ?? PokerTournament::DEFAULT_BUY_IN),
            'starting_stack' => (int) ($data['starting_stack'] ?? PokerTournament::DEFAULT_STARTING_STACK),
            'max_players' => (int) ($data['max_players'] ?? PokerTournament::DEFAULT_MAX_PLAYERS),
            'small_blind' => (int) ($data['small_blind'] ?? PokerTournament::DEFAULT_SMALL_BLIND),
            'big_blind' => (int) ($data['big_blind'] ?? PokerTournament::DEFAULT_BIG_BLIND),
            'blind_level_minutes' => (int) ($data['blind_level_minutes'] ?? PokerTournament::DEFAULT_BLIND_LEVEL_MINUTES),
            'current_blind_level' => 1,
            'payout_structure' => $data['payout_structure'] ?? PokerTournament::DEFAULT_PAYOUT_STRUCTURE,
            'paid_places_count' => count($data['payout_structure'] ?? PokerTournament::DEFAULT_PAYOUT_STRUCTURE),
            'starts_at' => $data['starts_at'] ?? null,
            'is_final_table' => false,
            'allow_reentry' => (bool) ($data['allow_reentry'] ?? true),
            'max_reentries_per_player' => (int) ($data['max_reentries_per_player'] ?? PokerTournament::DEFAULT_MAX_REENTRIES_PER_PLAYER),
            'reentry_buy_in' => (int) ($data['reentry_buy_in'] ?? ($data['buy_in'] ?? PokerTournament::DEFAULT_BUY_IN)),
            'reentry_stack' => (int) ($data['reentry_stack'] ?? ($data['starting_stack'] ?? PokerTournament::DEFAULT_STARTING_STACK)),
            'addon_enabled' => (bool) ($data['addon_enabled'] ?? true),
            'addon_buy_in' => (int) ($data['addon_buy_in'] ?? ($data['buy_in'] ?? PokerTournament::DEFAULT_BUY_IN)),
            'addon_stack' => (int) ($data['addon_stack'] ?? max(1, (int) floor(((int) ($data['starting_stack'] ?? PokerTournament::DEFAULT_STARTING_STACK)) * PokerTournament::DEFAULT_ADDON_STACK_RATIO))),
            'addon_available_until_blind_level' => (int) ($data['addon_available_until_blind_level'] ?? PokerTournament::DEFAULT_ADDON_AVAILABLE_UNTIL_BLIND_LEVEL),
        ]);

        $this->refreshResumeSnapshot($tournament->fresh(['participants.user']));

        return $tournament->fresh(['participants.user']);
    }

    public function register(PokerTournament $tournament, User $user): PokerTournamentParticipant
    {
        return DB::transaction(function () use ($tournament, $user): PokerTournamentParticipant {
            /** @var PokerTournament $lockedTournament */
            $lockedTournament = PokerTournament::query()->whereKey($tournament->id)->lockForUpdate()->firstOrFail();

            if (! $lockedTournament->isRegistering()) {
                throw new DomainException('Inscrições encerradas para este torneio.');
            }

            if (! $lockedTournament->hasAvailableSeat()) {
                throw new DomainException('Torneio lotado.');
            }

            $alreadyRegistered = PokerTournamentParticipant::query()
                ->where('poker_tournament_id', $lockedTournament->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyRegistered) {
                throw new DomainException('Você já está inscrito neste torneio.');
            }

            /** @var User $lockedUser */
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $buyIn = (int) $lockedTournament->buy_in;

            if ((int) $lockedUser->poker_bankroll < $buyIn) {
                throw new DomainException('Bankroll insuficiente para pagar o buy-in do torneio.');
            }

            $balanceBefore = (int) $lockedUser->poker_bankroll;
            $balanceAfter = $balanceBefore - $buyIn;

            $lockedUser->forceFill(['poker_bankroll' => $balanceAfter])->save();

            /** @var PokerTournamentParticipant $participant */
            $participant = PokerTournamentParticipant::query()->create([
                'poker_tournament_id' => $lockedTournament->id,
                'user_id' => $lockedUser->id,
                'status' => PokerTournamentParticipant::STATUS_REGISTERED,
                'starting_stack' => (int) $lockedTournament->starting_stack,
                'current_stack' => (int) $lockedTournament->starting_stack,
                'registered_at' => now(),
                'reentries_count' => 0,
                'addons_count' => 0,
            ]);

            $lockedTournament->forceFill([
                'registered_players_count' => (int) $lockedTournament->registered_players_count + 1,
                'prize_pool' => (int) $lockedTournament->prize_pool + $buyIn,
            ])->save();

            PokerBankrollTransaction::query()->create([
                'user_id' => $lockedUser->id,
                'type' => PokerBankrollTransaction::TYPE_TOURNAMENT_BUY_IN,
                'amount' => -$buyIn,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'metadata' => [
                    'phase' => '12.12.10',
                    'reason' => 'Inscrição em torneio de poker.',
                    'poker_tournament_id' => $lockedTournament->id,
                    'tournament_name' => $lockedTournament->name,
                    'starting_stack' => (int) $lockedTournament->starting_stack,
                ],
            ]);

            $this->refreshResumeSnapshot($lockedTournament->fresh(['participants.user']));

            return $participant->fresh(['tournament', 'user']);
        });
    }


    public function registerBot(PokerTournament $tournament): PokerTournamentParticipant
    {
        return DB::transaction(function () use ($tournament): PokerTournamentParticipant {
            /** @var PokerTournament $lockedTournament */
            $lockedTournament = PokerTournament::query()->whereKey($tournament->id)->lockForUpdate()->firstOrFail();

            if (! $lockedTournament->isRegistering()) {
                throw new DomainException('Inscrições encerradas para este torneio.');
            }

            if (! $lockedTournament->hasAvailableSeat()) {
                throw new DomainException('Torneio lotado.');
            }

            $botNumber = PokerTournamentParticipant::query()
                ->where('poker_tournament_id', $lockedTournament->id)
                ->count() + 1;

            /** @var User $bot */
            $bot = User::query()->create([
                'name' => 'Bot Torneio '.$botNumber,
                'email' => 'bot-torneio-'.$lockedTournament->id.'-'.$botNumber.'-'.Str::lower(Str::random(8)).'@pokerabs.local',
                'password' => Hash::make(Str::random(32)),
                'poker_bankroll' => 100000,
            ]);

            return $this->register($lockedTournament, $bot);
        });
    }

    public function start(PokerTournament $tournament): PokerTournament
    {
        return DB::transaction(function () use ($tournament): PokerTournament {
            /** @var PokerTournament $lockedTournament */
            $lockedTournament = PokerTournament::query()
                ->with('participants')
                ->whereKey($tournament->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedTournament->isRegistering()) {
                throw new DomainException('Apenas torneios com inscrições abertas podem ser iniciados.');
            }

            if ($lockedTournament->participants()->count() < 2) {
                throw new DomainException('É necessário ter ao menos 2 jogadores inscritos para iniciar o torneio.');
            }

            PokerTournamentParticipant::query()
                ->where('poker_tournament_id', $lockedTournament->id)
                ->where('status', PokerTournamentParticipant::STATUS_REGISTERED)
                ->update(['status' => PokerTournamentParticipant::STATUS_ACTIVE]);

            $lockedTournament->forceFill([
                'status' => PokerTournament::STATUS_RUNNING,
                'started_at' => now(),
                'current_blind_level' => max(1, (int) $lockedTournament->current_blind_level),
                'next_blind_at' => now()->addMinutes(max(1, (int) $lockedTournament->blind_level_minutes)),
            ])->save();

            $this->prepareFinalTableIfEligible($lockedTournament);

            $freshTournament = $lockedTournament->fresh(['participants.user', 'runtimeTable']);
            app(PokerTournamentTableBridgeService::class)->ensureRuntimeTable($freshTournament);
            $freshTournament = $lockedTournament->fresh(['participants.user', 'runtimeTable']);
            $this->refreshResumeSnapshot($freshTournament);

            return $freshTournament;
        });
    }


    public function reenter(PokerTournament $tournament, PokerTournamentParticipant $participant): PokerTournamentParticipant
    {
        return DB::transaction(function () use ($tournament, $participant): PokerTournamentParticipant {
            /** @var PokerTournament $lockedTournament */
            $lockedTournament = PokerTournament::query()
                ->whereKey($tournament->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTournament->status !== PokerTournament::STATUS_RUNNING) {
                throw new DomainException('Reentrada só pode ser registrada com o torneio em andamento.');
            }

            if (! (bool) $lockedTournament->allow_reentry) {
                throw new DomainException('Este torneio não permite reentrada.');
            }

            /** @var PokerTournamentParticipant $lockedParticipant */
            $lockedParticipant = PokerTournamentParticipant::query()
                ->where('poker_tournament_id', $lockedTournament->id)
                ->whereKey($participant->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedParticipant->status !== PokerTournamentParticipant::STATUS_ELIMINATED) {
                throw new DomainException('Reentrada só pode ser usada por jogador eliminado.');
            }

            $maxReentries = max(0, (int) $lockedTournament->max_reentries_per_player);

            if ((int) $lockedParticipant->reentries_count >= $maxReentries) {
                throw new DomainException('Limite de reentradas atingido para este jogador.');
            }

            $buyIn = (int) ($lockedTournament->reentry_buy_in ?: $lockedTournament->buy_in);
            $stack = (int) ($lockedTournament->reentry_stack ?: $lockedTournament->starting_stack);

            /** @var User $user */
            $user = User::query()->whereKey($lockedParticipant->user_id)->lockForUpdate()->firstOrFail();

            if ((int) $user->poker_bankroll < $buyIn) {
                throw new DomainException('Bankroll insuficiente para pagar a reentrada.');
            }

            $balanceBefore = (int) $user->poker_bankroll;
            $balanceAfter = $balanceBefore - $buyIn;

            $user->forceFill(['poker_bankroll' => $balanceAfter])->save();

            $lockedParticipant->forceFill([
                'status' => PokerTournamentParticipant::STATUS_ACTIVE,
                'current_stack' => $stack,
                'finish_position' => null,
                'eliminated_at' => null,
                'reentered_at' => now(),
                'reentries_count' => (int) $lockedParticipant->reentries_count + 1,
            ])->save();

            $lockedTournament->forceFill([
                'prize_pool' => (int) $lockedTournament->prize_pool + $buyIn,
            ])->save();

            PokerBankrollTransaction::query()->create([
                'user_id' => $user->id,
                'type' => PokerBankrollTransaction::TYPE_TOURNAMENT_REENTRY,
                'amount' => -$buyIn,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'metadata' => [
                    'phase' => '12.12.10',
                    'reason' => 'Reentrada em torneio de poker.',
                    'poker_tournament_id' => $lockedTournament->id,
                    'poker_tournament_participant_id' => $lockedParticipant->id,
                    'stack_received' => $stack,
                    'reentries_count' => (int) $lockedParticipant->reentries_count,
                ],
            ]);

            $this->refreshResumeSnapshot($lockedTournament->fresh(['participants.user']));

            return $lockedParticipant->fresh(['tournament', 'user']);
        });
    }

    public function addOn(PokerTournament $tournament, PokerTournamentParticipant $participant): PokerTournamentParticipant
    {
        return DB::transaction(function () use ($tournament, $participant): PokerTournamentParticipant {
            /** @var PokerTournament $lockedTournament */
            $lockedTournament = PokerTournament::query()
                ->whereKey($tournament->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTournament->status !== PokerTournament::STATUS_RUNNING) {
                throw new DomainException('Add-on só pode ser registrado com o torneio em andamento.');
            }

            if (! (bool) $lockedTournament->addon_enabled) {
                throw new DomainException('Este torneio não permite add-on.');
            }

            $limitLevel = (int) ($lockedTournament->addon_available_until_blind_level ?: PokerTournament::DEFAULT_ADDON_AVAILABLE_UNTIL_BLIND_LEVEL);

            if ((int) $lockedTournament->current_blind_level > $limitLevel) {
                throw new DomainException('A janela de add-on já foi encerrada.');
            }

            /** @var PokerTournamentParticipant $lockedParticipant */
            $lockedParticipant = PokerTournamentParticipant::query()
                ->where('poker_tournament_id', $lockedTournament->id)
                ->whereKey($participant->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedParticipant->status !== PokerTournamentParticipant::STATUS_ACTIVE) {
                throw new DomainException('Add-on só pode ser usado por jogador ativo.');
            }

            if ((int) $lockedParticipant->addons_count >= 1) {
                throw new DomainException('Este jogador já usou o add-on neste torneio.');
            }

            $buyIn = (int) ($lockedTournament->addon_buy_in ?: $lockedTournament->buy_in);
            $stack = (int) ($lockedTournament->addon_stack ?: max(1, (int) floor((int) $lockedTournament->starting_stack * PokerTournament::DEFAULT_ADDON_STACK_RATIO)));

            /** @var User $user */
            $user = User::query()->whereKey($lockedParticipant->user_id)->lockForUpdate()->firstOrFail();

            if ((int) $user->poker_bankroll < $buyIn) {
                throw new DomainException('Bankroll insuficiente para pagar o add-on.');
            }

            $balanceBefore = (int) $user->poker_bankroll;
            $balanceAfter = $balanceBefore - $buyIn;

            $user->forceFill(['poker_bankroll' => $balanceAfter])->save();

            $lockedParticipant->forceFill([
                'current_stack' => (int) $lockedParticipant->current_stack + $stack,
                'addons_count' => (int) $lockedParticipant->addons_count + 1,
                'addon_taken_at' => now(),
            ])->save();

            $lockedTournament->forceFill([
                'prize_pool' => (int) $lockedTournament->prize_pool + $buyIn,
            ])->save();

            PokerBankrollTransaction::query()->create([
                'user_id' => $user->id,
                'type' => PokerBankrollTransaction::TYPE_TOURNAMENT_ADDON,
                'amount' => -$buyIn,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'metadata' => [
                    'phase' => '12.12.10',
                    'reason' => 'Add-on em torneio de poker.',
                    'poker_tournament_id' => $lockedTournament->id,
                    'poker_tournament_participant_id' => $lockedParticipant->id,
                    'stack_received' => $stack,
                ],
            ]);

            $this->refreshResumeSnapshot($lockedTournament->fresh(['participants.user']));

            return $lockedParticipant->fresh(['tournament', 'user']);
        });
    }


    public function prepareFinalTable(PokerTournament $tournament): PokerTournament
    {
        return DB::transaction(function () use ($tournament): PokerTournament {
            /** @var PokerTournament $lockedTournament */
            $lockedTournament = PokerTournament::query()
                ->with(['participants.user'])
                ->whereKey($tournament->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTournament->status !== PokerTournament::STATUS_RUNNING) {
                throw new DomainException('Mesa final só pode ser organizada com o torneio em andamento.');
            }

            if ((bool) $lockedTournament->is_final_table) {
                throw new DomainException('A mesa final já foi organizada para este torneio.');
            }

            $activeCount = $this->activeParticipantsFor($lockedTournament)->count();

            if ($activeCount < 2) {
                throw new DomainException('Não há jogadores ativos suficientes para organizar a mesa final.');
            }

            if ($activeCount > PokerTournament::FINAL_TABLE_MAX_PLAYERS) {
                throw new DomainException('A mesa final só pode ser formada com até 9 jogadores ativos.');
            }

            $this->markFinalTable($lockedTournament);

            $freshTournament = $lockedTournament->fresh(['participants.user']);
            $this->refreshResumeSnapshot($freshTournament);

            return $freshTournament;
        });
    }


    public function advanceBlindLevel(PokerTournament $tournament): PokerTournament
    {
        return DB::transaction(function () use ($tournament): PokerTournament {
            /** @var PokerTournament $lockedTournament */
            $lockedTournament = PokerTournament::query()
                ->whereKey($tournament->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTournament->status !== PokerTournament::STATUS_RUNNING) {
                throw new DomainException('Blinds só podem avançar com o torneio em andamento.');
            }

            $smallBlind = max(1, (int) $lockedTournament->small_blind);
            $bigBlind = max(2, (int) $lockedTournament->big_blind);
            $levelMinutes = max(1, (int) $lockedTournament->blind_level_minutes);

            $lockedTournament->forceFill([
                'current_blind_level' => max(1, (int) $lockedTournament->current_blind_level) + 1,
                'small_blind' => $smallBlind * 2,
                'big_blind' => $bigBlind * 2,
                'next_blind_at' => now()->addMinutes($levelMinutes),
            ])->save();

            $freshTournament = $lockedTournament->fresh(['participants.user']);
            $this->refreshResumeSnapshot($freshTournament);

            return $freshTournament;
        });
    }

    public function eliminate(PokerTournament $tournament, PokerTournamentParticipant $participant): PokerTournament
    {
        return DB::transaction(function () use ($tournament, $participant): PokerTournament {
            /** @var PokerTournament $lockedTournament */
            $lockedTournament = PokerTournament::query()
                ->whereKey($tournament->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTournament->status !== PokerTournament::STATUS_RUNNING) {
                throw new DomainException('Só é possível eliminar jogadores com o torneio em andamento.');
            }

            /** @var PokerTournamentParticipant $lockedParticipant */
            $lockedParticipant = PokerTournamentParticipant::query()
                ->where('poker_tournament_id', $lockedTournament->id)
                ->whereKey($participant->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedParticipant->status !== PokerTournamentParticipant::STATUS_ACTIVE) {
                throw new DomainException('Este participante não está ativo no torneio.');
            }

            $activeBefore = PokerTournamentParticipant::query()
                ->where('poker_tournament_id', $lockedTournament->id)
                ->where('status', PokerTournamentParticipant::STATUS_ACTIVE)
                ->count();

            if ($activeBefore <= 1) {
                throw new DomainException('Não há jogadores ativos suficientes para registrar eliminação.');
            }

            $lockedParticipant->forceFill([
                'status' => PokerTournamentParticipant::STATUS_ELIMINATED,
                'current_stack' => 0,
                'finish_position' => $activeBefore,
                'eliminated_at' => now(),
            ])->save();

            $this->finishIfOnlyOneActive($lockedTournament);

            if ($lockedTournament->fresh()->status === PokerTournament::STATUS_RUNNING) {
                $this->prepareFinalTableIfEligible($lockedTournament);
            }

            return $lockedTournament->fresh(['participants.user']);
        });
    }


    public function closeOfficially(PokerTournament $tournament): PokerTournament
    {
        return DB::transaction(function () use ($tournament): PokerTournament {
            /** @var PokerTournament $lockedTournament */
            $lockedTournament = PokerTournament::query()
                ->whereKey($tournament->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTournament->status === PokerTournament::STATUS_RUNNING) {
                $this->finishIfOnlyOneActive($lockedTournament);
                $lockedTournament->refresh();
            }

            if ($lockedTournament->status !== PokerTournament::STATUS_FINISHED) {
                throw new DomainException('O torneio ainda não possui campeão definido para encerramento oficial.');
            }

            $freshTournament = $lockedTournament->fresh(['participants.user']);
            $this->refreshResumeSnapshot($freshTournament);

            return $freshTournament;
        });
    }


    private function prepareFinalTableIfEligible(PokerTournament $tournament): void
    {
        /** @var PokerTournament $freshTournament */
        $freshTournament = PokerTournament::query()
            ->whereKey($tournament->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($freshTournament->status !== PokerTournament::STATUS_RUNNING || (bool) $freshTournament->is_final_table) {
            return;
        }

        $activeCount = $this->activeParticipantsFor($freshTournament)->count();

        if ($activeCount >= 2 && $activeCount <= PokerTournament::FINAL_TABLE_MAX_PLAYERS) {
            $this->markFinalTable($freshTournament);
        }
    }

    private function markFinalTable(PokerTournament $tournament): void
    {
        $seatMap = $this->activeParticipantsFor($tournament)
            ->sortByDesc(static fn (PokerTournamentParticipant $participant): int => (int) $participant->current_stack)
            ->values()
            ->map(static fn (PokerTournamentParticipant $participant, int $index): array => [
                'seat' => $index + 1,
                'participantId' => $participant->id,
                'userId' => $participant->user_id,
                'name' => $participant->user?->name ?? 'Jogador',
                'stack' => (int) $participant->current_stack,
            ])
            ->all();

        $tournament->forceFill([
            'is_final_table' => true,
            'final_table_started_at' => now(),
            'final_table_seat_map' => $seatMap,
        ])->save();
    }

    private function activeParticipantsFor(PokerTournament $tournament)
    {
        return PokerTournamentParticipant::query()
            ->with('user')
            ->where('poker_tournament_id', $tournament->id)
            ->where('status', PokerTournamentParticipant::STATUS_ACTIVE)
            ->lockForUpdate()
            ->get();
    }


    private function finishIfOnlyOneActive(PokerTournament $tournament): void
    {
        $activeParticipants = PokerTournamentParticipant::query()
            ->where('poker_tournament_id', $tournament->id)
            ->where('status', PokerTournamentParticipant::STATUS_ACTIVE)
            ->lockForUpdate()
            ->get();

        if ($activeParticipants->count() !== 1) {
            return;
        }

        /** @var PokerTournamentParticipant $winner */
        $winner = $activeParticipants->first();

        $winner->forceFill([
            'status' => PokerTournamentParticipant::STATUS_WINNER,
            'finish_position' => 1,
            'current_stack' => max((int) $winner->current_stack, 1),
        ])->save();

        $this->applyTournamentPayouts($tournament->fresh());

        $tournament->forceFill([
            'status' => PokerTournament::STATUS_FINISHED,
            'finished_at' => now(),
        ])->save();
    }


    private function applyTournamentPayouts(PokerTournament $tournament): void
    {
        $participants = PokerTournamentParticipant::query()
            ->where('poker_tournament_id', $tournament->id)
            ->whereNotNull('finish_position')
            ->orderBy('finish_position')
            ->lockForUpdate()
            ->get();

        if ($participants->isEmpty()) {
            return;
        }

        $payouts = $this->payoutPlanFor($tournament, $participants->count());
        $distributed = 0;
        $prizePool = (int) $tournament->prize_pool;
        $lastPaidPosition = collect($payouts)
            ->filter(static fn (array $row): bool => (int) $row['amount'] > 0)
            ->max('position');

        foreach ($participants as $participant) {
            $position = (int) $participant->finish_position;
            $amount = (int) (collect($payouts)->firstWhere('position', $position)['amount'] ?? 0);

            if ($position === $lastPaidPosition) {
                $amount = max(0, $prizePool - $distributed);
            }

            $distributed += $amount;

            if ((int) $participant->prize_amount === $amount) {
                continue;
            }

            $participant->forceFill(['prize_amount' => $amount])->save();

            if ($amount <= 0) {
                continue;
            }

            /** @var User $user */
            $user = User::query()->whereKey($participant->user_id)->lockForUpdate()->firstOrFail();
            $balanceBefore = (int) $user->poker_bankroll;
            $balanceAfter = $balanceBefore + $amount;

            $user->forceFill(['poker_bankroll' => $balanceAfter])->save();

            PokerBankrollTransaction::query()->create([
                'user_id' => $user->id,
                'type' => PokerBankrollTransaction::TYPE_TOURNAMENT_PAYOUT,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'metadata' => [
                    'phase' => '12.12.10',
                    'reason' => 'Premiação de torneio de poker.',
                    'poker_tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'finish_position' => $position,
                    'payout_percent' => collect($payouts)->firstWhere('position', $position)['percent'] ?? 0,
                ],
            ]);
        }
    }

    /** @return array<int, array{position:int, percent:int, amount:int}> */
    private function payoutPlanFor(PokerTournament $tournament, int $participantsCount): array
    {
        $structure = $tournament->payout_structure ?: PokerTournament::DEFAULT_PAYOUT_STRUCTURE;
        $prizePool = (int) $tournament->prize_pool;
        $paidPlaces = max(1, min($participantsCount, count($structure)));
        $plan = [];
        $allocated = 0;

        foreach (array_values($structure) as $index => $row) {
            $position = (int) ($row['position'] ?? ($index + 1));

            if ($position > $paidPlaces) {
                continue;
            }

            $percent = (int) ($row['percent'] ?? 0);
            $amount = (int) floor($prizePool * ($percent / 100));
            $allocated += $amount;

            $plan[] = [
                'position' => $position,
                'percent' => $percent,
                'amount' => $amount,
            ];
        }

        if ($plan === []) {
            $plan[] = ['position' => 1, 'percent' => 100, 'amount' => $prizePool];
        }

        if (count($plan) === 1) {
            $plan[0]['percent'] = 100;
            $plan[0]['amount'] = $prizePool;
            return $plan;
        }

        $remaining = $prizePool - $allocated;
        $lastIndex = array_key_last($plan);
        $plan[$lastIndex]['amount'] += $remaining;

        return $plan;
    }

    /** @return array<string, mixed> */
    private function serializeTournament(PokerTournament $tournament, ?User $user): array
    {
        $participants = $tournament->participants
            ->sortBy(fn (PokerTournamentParticipant $participant): array => [
                $participant->finish_position ?? 999,
                $participant->registered_at?->timestamp ?? 0,
            ])
            ->map(fn (PokerTournamentParticipant $participant): array => [
                'id' => $participant->id,
                'userId' => $participant->user_id,
                'name' => $participant->user?->name ?? 'Jogador',
                'status' => $participant->status,
                'statusLabel' => match ($participant->status) {
                    PokerTournamentParticipant::STATUS_REGISTERED => 'Inscrito',
                    PokerTournamentParticipant::STATUS_ACTIVE => 'Ativo',
                    PokerTournamentParticipant::STATUS_ELIMINATED => 'Eliminado',
                    PokerTournamentParticipant::STATUS_WINNER => 'Campeão',
                    default => (string) $participant->status,
                },
                'stack' => (int) $participant->current_stack,
                'finishPosition' => $participant->finish_position,
                'prizeAmount' => (int) $participant->prize_amount,
                'reentriesCount' => (int) $participant->reentries_count,
                'addonsCount' => (int) $participant->addons_count,
                'reenteredAt' => $participant->reentered_at?->format('d/m/Y H:i'),
                'addonTakenAt' => $participant->addon_taken_at?->format('d/m/Y H:i'),
                'registeredAt' => $participant->registered_at?->format('d/m/Y H:i'),
                'eliminatedAt' => $participant->eliminated_at?->format('d/m/Y H:i'),
                'canReenter' => $tournament->status === PokerTournament::STATUS_RUNNING
                    && (bool) $tournament->allow_reentry
                    && $participant->status === PokerTournamentParticipant::STATUS_ELIMINATED
                    && (int) $participant->reentries_count < max(0, (int) $tournament->max_reentries_per_player),
                'canAddon' => $tournament->status === PokerTournament::STATUS_RUNNING
                    && (bool) $tournament->addon_enabled
                    && $participant->status === PokerTournamentParticipant::STATUS_ACTIVE
                    && (int) $participant->addons_count < 1
                    && (int) $tournament->current_blind_level <= (int) ($tournament->addon_available_until_blind_level ?: PokerTournament::DEFAULT_ADDON_AVAILABLE_UNTIL_BLIND_LEVEL),
            ])
            ->values()
            ->all();

        $isRegistered = $user !== null && collect($participants)->contains(
            static fn (array $participant): bool => (int) $participant['userId'] === (int) $user->id,
        );

        return [
            'id' => $tournament->id,
            'runtimeTable' => app(PokerTournamentTableBridgeService::class)->runtimePayloadFor($tournament),
            'name' => $tournament->name,
            'status' => $tournament->status,
            'statusLabel' => $this->statusLabel((string) $tournament->status),
            'buyIn' => (int) $tournament->buy_in,
            'startingStack' => (int) $tournament->starting_stack,
            'maxPlayers' => (int) $tournament->max_players,
            'blindStructure' => [
                'phase' => '12.12.10',
                'currentLevel' => max(1, (int) $tournament->current_blind_level),
                'smallBlind' => max(1, (int) $tournament->small_blind),
                'bigBlind' => max(2, (int) $tournament->big_blind),
                'levelMinutes' => max(1, (int) $tournament->blind_level_minutes),
                'nextBlindAt' => $tournament->next_blind_at?->format('d/m/Y H:i'),
                'nextBlindAtIso' => $tournament->next_blind_at?->toIso8601String(),
            ],
            'registeredPlayers' => (int) ($tournament->participants_count ?? $tournament->registered_players_count),
            'activePlayers' => collect($participants)->where('status', PokerTournamentParticipant::STATUS_ACTIVE)->count(),
            'prizePool' => (int) $tournament->prize_pool,
            'payoutPlan' => $this->payoutPlanFor($tournament, max(1, count($participants))),
            'paidPlacesCount' => max(1, min(count($participants) ?: (int) $tournament->max_players, count($tournament->payout_structure ?: PokerTournament::DEFAULT_PAYOUT_STRUCTURE))),
            'reentryAddon' => [
                'phase' => '12.12.10',
                'allowReentry' => (bool) $tournament->allow_reentry,
                'maxReentriesPerPlayer' => (int) $tournament->max_reentries_per_player,
                'reentryBuyIn' => (int) ($tournament->reentry_buy_in ?: $tournament->buy_in),
                'reentryStack' => (int) ($tournament->reentry_stack ?: $tournament->starting_stack),
                'addonEnabled' => (bool) $tournament->addon_enabled,
                'addonBuyIn' => (int) ($tournament->addon_buy_in ?: $tournament->buy_in),
                'addonStack' => (int) ($tournament->addon_stack ?: max(1, (int) floor((int) $tournament->starting_stack * PokerTournament::DEFAULT_ADDON_STACK_RATIO))),
                'addonAvailableUntilBlindLevel' => (int) ($tournament->addon_available_until_blind_level ?: PokerTournament::DEFAULT_ADDON_AVAILABLE_UNTIL_BLIND_LEVEL),
            ],
            'finalTable' => [
                'phase' => '12.12.10',
                'enabled' => (bool) $tournament->is_final_table,
                'maxPlayers' => PokerTournament::FINAL_TABLE_MAX_PLAYERS,
                'startedAt' => $tournament->final_table_started_at?->format('d/m/Y H:i'),
                'seatMap' => $tournament->final_table_seat_map ?: [],
            ],
            'lobbySummary' => $this->tournamentLobbySummaryFor($tournament, $participants),
            'officialResult' => $this->officialResultFor($tournament, $participants),
            'startsAt' => $tournament->starts_at?->format('d/m/Y H:i'),
            'startedAt' => $tournament->started_at?->format('d/m/Y H:i'),
            'finishedAt' => $tournament->finished_at?->format('d/m/Y H:i'),
            'isRegistered' => $isRegistered,
            'resumeState' => $this->resumeStateFor($tournament, $participants),
            'canRegister' => $tournament->isRegistering()
                && ! $isRegistered
                && (int) ($tournament->participants_count ?? $tournament->registered_players_count) < (int) $tournament->max_players,
            'canRegisterBot' => $tournament->isRegistering()
                && (int) ($tournament->participants_count ?? $tournament->registered_players_count) < (int) $tournament->max_players,
            'canStart' => $tournament->isRegistering() && count($participants) >= 2,
            'canAdvanceBlind' => $tournament->status === PokerTournament::STATUS_RUNNING,
            'canPrepareFinalTable' => $tournament->status === PokerTournament::STATUS_RUNNING
                && ! (bool) $tournament->is_final_table
                && collect($participants)->where('status', PokerTournamentParticipant::STATUS_ACTIVE)->count() >= 2
                && collect($participants)->where('status', PokerTournamentParticipant::STATUS_ACTIVE)->count() <= PokerTournament::FINAL_TABLE_MAX_PLAYERS,
            'canCloseOfficially' => $tournament->status === PokerTournament::STATUS_FINISHED
                || ($tournament->status === PokerTournament::STATUS_RUNNING && collect($participants)->where('status', PokerTournamentParticipant::STATUS_ACTIVE)->count() === 1),
            'participants' => $participants,
            'ranking' => collect($participants)
                ->filter(static fn (array $participant): bool => $participant['finishPosition'] !== null || $participant['status'] === PokerTournamentParticipant::STATUS_ACTIVE)
                ->values()
                ->all(),
        ];
    }


    /**
     * @param array<int, array<string, mixed>> $participants
     * @return array<string, mixed>
     */
    private function officialResultFor(PokerTournament $tournament, array $participants): array
    {
        $collection = collect($participants);
        $champion = $collection->first(static fn (array $participant): bool => $participant['status'] === PokerTournamentParticipant::STATUS_WINNER)
            ?? $collection->firstWhere('finishPosition', 1);

        $podium = $collection
            ->filter(static fn (array $participant): bool => $participant['finishPosition'] !== null)
            ->sortBy('finishPosition')
            ->take(3)
            ->values()
            ->map(static fn (array $participant): array => [
                'position' => (int) $participant['finishPosition'],
                'name' => $participant['name'],
                'userId' => $participant['userId'],
                'prizeAmount' => (int) $participant['prizeAmount'],
                'status' => $participant['status'],
            ])
            ->all();

        $totalPaid = (int) $collection->sum(static fn (array $participant): int => (int) $participant['prizeAmount']);
        $isFinished = $tournament->status === PokerTournament::STATUS_FINISHED;

        return [
            'phase' => '12.12.10',
            'isFinished' => $isFinished,
            'champion' => $champion ? [
                'name' => $champion['name'],
                'userId' => $champion['userId'],
                'prizeAmount' => (int) $champion['prizeAmount'],
            ] : null,
            'podium' => $podium,
            'prizePool' => (int) $tournament->prize_pool,
            'totalPaid' => $totalPaid,
            'paidPlaces' => collect($podium)->where('prizeAmount', '>', 0)->count(),
            'finishedAt' => $tournament->finished_at?->format('d/m/Y H:i'),
            'summaryLabel' => $isFinished
                ? sprintf('Campeão definido%s.', $champion ? ': '.$champion['name'] : '')
                : 'Resultado oficial ainda pendente.',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $tournaments
     * @return array<string, mixed>
     */
    private function lobbyPayloadFor(array $tournaments): array
    {
        $collection = collect($tournaments);
        $nextToStart = $collection
            ->where('status', PokerTournament::STATUS_REGISTERING)
            ->sortByDesc(static fn (array $tournament): int => (int) ($tournament['lobbySummary']['occupancyPercent'] ?? 0))
            ->first();

        return [
            'phase' => '12.12.10',
            'title' => 'Lobby avançado de torneios e resultados oficiais',
            'nextToStart' => $nextToStart ? [
                'id' => $nextToStart['id'],
                'name' => $nextToStart['name'],
                'playersNeeded' => $nextToStart['lobbySummary']['playersNeededToStart'] ?? 0,
                'occupancyPercent' => $nextToStart['lobbySummary']['occupancyPercent'] ?? 0,
            ] : null,
            'quickFilters' => [
                ['value' => 'all', 'label' => 'Todos', 'count' => $collection->count()],
                ['value' => PokerTournament::STATUS_REGISTERING, 'label' => 'Abertos', 'count' => $collection->where('status', PokerTournament::STATUS_REGISTERING)->count()],
                ['value' => PokerTournament::STATUS_RUNNING, 'label' => 'Em andamento', 'count' => $collection->where('status', PokerTournament::STATUS_RUNNING)->count()],
                ['value' => PokerTournament::STATUS_FINISHED, 'label' => 'Finalizados', 'count' => $collection->where('status', PokerTournament::STATUS_FINISHED)->count()],
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $participants
     * @return array<string, mixed>
     */
    private function tournamentLobbySummaryFor(PokerTournament $tournament, array $participants): array
    {
        $registeredPlayers = count($participants);
        $maxPlayers = max(1, (int) $tournament->max_players);
        $activePlayers = collect($participants)->where('status', PokerTournamentParticipant::STATUS_ACTIVE)->count();
        $elapsedMinutes = null;

        if ($tournament->started_at !== null) {
            $end = $tournament->finished_at ?? now();
            $elapsedMinutes = max(0, $tournament->started_at->diffInMinutes($end));
        }

        return [
            'phase' => '12.12.10',
            'occupancyPercent' => min(100, (int) round(($registeredPlayers / $maxPlayers) * 100)),
            'availableSeats' => max(0, $maxPlayers - $registeredPlayers),
            'playersNeededToStart' => $tournament->status === PokerTournament::STATUS_REGISTERING ? max(0, 2 - $registeredPlayers) : 0,
            'activePlayers' => $activePlayers,
            'elapsedMinutes' => $elapsedMinutes,
            'headline' => match ($tournament->status) {
                PokerTournament::STATUS_REGISTERING => $registeredPlayers >= 2 ? 'Pronto para iniciar' : 'Aguardando jogadores',
                PokerTournament::STATUS_RUNNING => 'Torneio em andamento',
                PokerTournament::STATUS_FINISHED => 'Torneio finalizado',
                default => 'Status do torneio',
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshResumeSnapshot(PokerTournament $tournament): array
    {
        $participants = $tournament->participants()->with('user')->get();
        $activePlayers = $participants->where('status', PokerTournamentParticipant::STATUS_ACTIVE)->count();
        $registeredPlayers = $participants->count();
        $snapshot = [
            'phase' => '12.12.10',
            'tournamentId' => $tournament->id,
            'status' => $tournament->status,
            'registeredPlayers' => $registeredPlayers,
            'activePlayers' => $activePlayers,
            'currentBlindLevel' => max(1, (int) $tournament->current_blind_level),
            'smallBlind' => max(1, (int) $tournament->small_blind),
            'bigBlind' => max(2, (int) $tournament->big_blind),
            'prizePool' => (int) $tournament->prize_pool,
            'isFinalTable' => (bool) $tournament->is_final_table,
            'lastEvent' => $this->resumeLastEventFor($tournament, $activePlayers),
            'updatedAt' => now()->toIso8601String(),
        ];

        $token = $tournament->resume_token ?: hash('sha256', 'poker-tournament-'.$tournament->id.'-'.now()->timestamp.'-'.random_int(1, PHP_INT_MAX));

        $tournament->forceFill([
            'resume_token' => $token,
            'resume_snapshot' => $snapshot,
            'last_snapshot_at' => now(),
        ])->save();

        return $snapshot;
    }

    /**
     * @param array<int, array<string, mixed>> $participants
     * @return array<string, mixed>
     */
    private function resumeStateFor(PokerTournament $tournament, array $participants): array
    {
        $snapshot = $tournament->resume_snapshot;

        if (! is_array($snapshot)) {
            $snapshot = [
                'phase' => '12.12.10',
                'tournamentId' => $tournament->id,
                'status' => $tournament->status,
                'registeredPlayers' => count($participants),
                'activePlayers' => collect($participants)->where('status', PokerTournamentParticipant::STATUS_ACTIVE)->count(),
                'currentBlindLevel' => max(1, (int) $tournament->current_blind_level),
                'smallBlind' => max(1, (int) $tournament->small_blind),
                'bigBlind' => max(2, (int) $tournament->big_blind),
                'prizePool' => (int) $tournament->prize_pool,
                'isFinalTable' => (bool) $tournament->is_final_table,
                'lastEvent' => $this->resumeLastEventFor($tournament, collect($participants)->where('status', PokerTournamentParticipant::STATUS_ACTIVE)->count()),
                'updatedAt' => $tournament->updated_at?->toIso8601String(),
            ];
        }

        return [
            'phase' => '12.12.10',
            'token' => $tournament->resume_token,
            'isRestorable' => $tournament->status !== PokerTournament::STATUS_FINISHED,
            'lastSnapshotAt' => $tournament->last_snapshot_at?->format('d/m/Y H:i'),
            'lastSnapshotAtIso' => $tournament->last_snapshot_at?->toIso8601String(),
            'snapshot' => $snapshot,
            'message' => match ($tournament->status) {
                PokerTournament::STATUS_REGISTERING => 'Torneio salvo e aguardando início.',
                PokerTournament::STATUS_RUNNING => 'Torneio em andamento pode ser retomado do lobby.',
                PokerTournament::STATUS_FINISHED => 'Torneio finalizado e preservado no histórico.',
                default => 'Estado do torneio preservado.',
            },
        ];
    }

    private function resumeLastEventFor(PokerTournament $tournament, int $activePlayers): string
    {
        return match ($tournament->status) {
            PokerTournament::STATUS_REGISTERING => 'Inscrições abertas',
            PokerTournament::STATUS_RUNNING => $activePlayers <= PokerTournament::FINAL_TABLE_MAX_PLAYERS && (bool) $tournament->is_final_table
                ? 'Mesa final em andamento'
                : 'Torneio em andamento',
            PokerTournament::STATUS_FINISHED => 'Torneio encerrado',
            default => 'Torneio atualizado',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            PokerTournament::STATUS_REGISTERING => 'Inscrições abertas',
            PokerTournament::STATUS_RUNNING => 'Em andamento',
            PokerTournament::STATUS_FINISHED => 'Finalizado',
            default => $status,
        };
    }
}
