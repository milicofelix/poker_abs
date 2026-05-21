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
            ->with(['participants.user'])
            ->withCount('participants')
            ->latest()
            ->get()
            ->map(fn (PokerTournament $tournament): array => $this->serializeTournament($tournament, $user))
            ->values()
            ->all();

        return [
            'phase' => '12.12.6',
            'summary' => [
                'total' => count($tournaments),
                'registering' => collect($tournaments)->where('status', PokerTournament::STATUS_REGISTERING)->count(),
                'running' => collect($tournaments)->where('status', PokerTournament::STATUS_RUNNING)->count(),
                'finished' => collect($tournaments)->where('status', PokerTournament::STATUS_FINISHED)->count(),
            ],
            'defaults' => [
                'buyIn' => PokerTournament::DEFAULT_BUY_IN,
                'startingStack' => PokerTournament::DEFAULT_STARTING_STACK,
                'maxPlayers' => PokerTournament::DEFAULT_MAX_PLAYERS,
                'smallBlind' => PokerTournament::DEFAULT_SMALL_BLIND,
                'bigBlind' => PokerTournament::DEFAULT_BIG_BLIND,
                'blindLevelMinutes' => PokerTournament::DEFAULT_BLIND_LEVEL_MINUTES,
                'payoutStructure' => PokerTournament::DEFAULT_PAYOUT_STRUCTURE,
                'finalTableMaxPlayers' => PokerTournament::FINAL_TABLE_MAX_PLAYERS,
            ],
            'tournaments' => $tournaments,
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): PokerTournament
    {
        return PokerTournament::query()->create([
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
        ]);
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
                    'phase' => '12.12.1',
                    'reason' => 'Inscrição em torneio de poker.',
                    'poker_tournament_id' => $lockedTournament->id,
                    'tournament_name' => $lockedTournament->name,
                    'starting_stack' => (int) $lockedTournament->starting_stack,
                ],
            ]);

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

            return $lockedTournament->fresh(['participants.user']);
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

            return $lockedTournament->fresh(['participants.user']);
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

            return $lockedTournament->fresh(['participants.user']);
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
                    'phase' => '12.12.6',
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
            ->map(static fn (PokerTournamentParticipant $participant): array => [
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
                'registeredAt' => $participant->registered_at?->format('d/m/Y H:i'),
                'eliminatedAt' => $participant->eliminated_at?->format('d/m/Y H:i'),
            ])
            ->values()
            ->all();

        $isRegistered = $user !== null && collect($participants)->contains(
            static fn (array $participant): bool => (int) $participant['userId'] === (int) $user->id,
        );

        return [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'status' => $tournament->status,
            'statusLabel' => $this->statusLabel((string) $tournament->status),
            'buyIn' => (int) $tournament->buy_in,
            'startingStack' => (int) $tournament->starting_stack,
            'maxPlayers' => (int) $tournament->max_players,
            'blindStructure' => [
                'phase' => '12.12.6',
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
            'finalTable' => [
                'phase' => '12.12.6',
                'enabled' => (bool) $tournament->is_final_table,
                'maxPlayers' => PokerTournament::FINAL_TABLE_MAX_PLAYERS,
                'startedAt' => $tournament->final_table_started_at?->format('d/m/Y H:i'),
                'seatMap' => $tournament->final_table_seat_map ?: [],
            ],
            'startsAt' => $tournament->starts_at?->format('d/m/Y H:i'),
            'startedAt' => $tournament->started_at?->format('d/m/Y H:i'),
            'finishedAt' => $tournament->finished_at?->format('d/m/Y H:i'),
            'isRegistered' => $isRegistered,
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
            'participants' => $participants,
            'ranking' => collect($participants)
                ->filter(static fn (array $participant): bool => $participant['finishPosition'] !== null || $participant['status'] === PokerTournamentParticipant::STATUS_ACTIVE)
                ->values()
                ->all(),
        ];
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
