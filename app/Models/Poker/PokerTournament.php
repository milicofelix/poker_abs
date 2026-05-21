<?php

namespace App\Models\Poker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PokerTournament extends Model
{
    use HasFactory;

    public const STATUS_REGISTERING = 'registering';
    public const STATUS_RUNNING = 'running';
    public const STATUS_FINISHED = 'finished';

    public const DEFAULT_BUY_IN = 1000;
    public const DEFAULT_STARTING_STACK = 5000;
    public const DEFAULT_MAX_PLAYERS = 9;
    public const DEFAULT_SMALL_BLIND = 25;
    public const DEFAULT_BIG_BLIND = 50;
    public const DEFAULT_BLIND_LEVEL_MINUTES = 10;
    public const DEFAULT_PAYOUT_STRUCTURE = [
        ['position' => 1, 'percent' => 70],
        ['position' => 2, 'percent' => 20],
        ['position' => 3, 'percent' => 10],
    ];
    public const FINAL_TABLE_MAX_PLAYERS = 9;

    protected $fillable = [
        'name',
        'status',
        'buy_in',
        'starting_stack',
        'max_players',
        'registered_players_count',
        'prize_pool',
        'starts_at',
        'started_at',
        'finished_at',
        'current_blind_level',
        'small_blind',
        'big_blind',
        'blind_level_minutes',
        'next_blind_at',
        'payout_structure',
        'paid_places_count',
        'is_final_table',
        'final_table_started_at',
        'final_table_seat_map',
    ];

    protected $casts = [
        'buy_in' => 'integer',
        'starting_stack' => 'integer',
        'max_players' => 'integer',
        'registered_players_count' => 'integer',
        'prize_pool' => 'integer',
        'starts_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'current_blind_level' => 'integer',
        'small_blind' => 'integer',
        'big_blind' => 'integer',
        'blind_level_minutes' => 'integer',
        'next_blind_at' => 'datetime',
        'payout_structure' => 'array',
        'paid_places_count' => 'integer',
        'is_final_table' => 'boolean',
        'final_table_started_at' => 'datetime',
        'final_table_seat_map' => 'array',
    ];

    /**
     * @return HasMany<PokerTournamentParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(PokerTournamentParticipant::class);
    }

    public function isRegistering(): bool
    {
        return $this->status === self::STATUS_REGISTERING;
    }

    public function hasAvailableSeat(): bool
    {
        return (int) $this->registered_players_count < (int) $this->max_players;
    }
}
