<?php

namespace App\Models\Poker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PokerTable extends Model
{
    use HasFactory;

    public const DEFAULT_MAX_PLAYERS = 2;
    public const CURRENT_ENGINE_MAX_PLAYERS = 2;
    public const MIN_DECLARED_MAX_PLAYERS = 2;
    public const FUTURE_MULTI_SEAT_TARGET = 6;
    public const DEFAULT_BUY_IN = 1000;
    public const MIN_BUY_IN = 200;
    public const MAX_BUY_IN = 10000;
    public const BUY_IN_STEP = 100;
    public const SUGGESTED_BUY_INS = [500, 1000, 2000, 5000, 10000];

    protected $fillable = [
        'name',
        'status',
        'small_blind',
        'big_blind',
        'buy_in',
        'max_players',
        'is_private',
        'invite_code',
    ];

    protected $casts = [
        'small_blind' => 'integer',
        'big_blind' => 'integer',
        'buy_in' => 'integer',
        'max_players' => 'integer',
        'is_private' => 'boolean',
    ];


    public function buyInAmount(): int
    {
        $buyIn = (int) ($this->buy_in ?: self::DEFAULT_BUY_IN);

        return min(self::MAX_BUY_IN, max(self::MIN_BUY_IN, $buyIn));
    }

    public function declaredMaxPlayers(): int
    {
        return max(self::MIN_DECLARED_MAX_PLAYERS, (int) ($this->max_players ?: self::DEFAULT_MAX_PLAYERS));
    }

    public function currentEngineMaxPlayers(): int
    {
        return min(self::CURRENT_ENGINE_MAX_PLAYERS, $this->declaredMaxPlayers());
    }

    public function minimumPlayersToStartCurrentEngine(): int
    {
        return min(self::CURRENT_ENGINE_MAX_PLAYERS, $this->declaredMaxPlayers());
    }

    public function isMultiSeatCandidate(): bool
    {
        return $this->declaredMaxPlayers() > self::CURRENT_ENGINE_MAX_PLAYERS;
    }

    public function engineMode(): string
    {
        return $this->isMultiSeatCandidate() ? 'multi_seat_preparation' : 'heads_up';
    }

    public function engineModeLabel(): string
    {
        return $this->isMultiSeatCandidate()
            ? 'Preparada para multi-seat; motor atual ainda heads-up'
            : 'Motor heads-up atual';
    }

    /**
     * @return array<string, mixed>
     */
    public function capacityPayload(): array
    {
        return [
            'maxPlayers' => $this->declaredMaxPlayers(),
            'currentEngineMaxPlayers' => $this->currentEngineMaxPlayers(),
            'minimumPlayersToStart' => $this->minimumPlayersToStartCurrentEngine(),
            'isMultiSeatCandidate' => $this->isMultiSeatCandidate(),
            'engineMode' => $this->engineMode(),
            'engineModeLabel' => $this->engineModeLabel(),
            'futureMultiSeatTarget' => self::FUTURE_MULTI_SEAT_TARGET,
            'defaultBuyIn' => self::DEFAULT_BUY_IN,
            'buyIn' => $this->buyInAmount(),
            'buyInStep' => self::BUY_IN_STEP,
        ];
    }

    /**
     * @return HasMany<PokerPlayer, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(PokerPlayer::class);
    }

    /**
     * @return HasMany<PokerTablePlayer, $this>
     */
    public function realPlayers(): HasMany
    {
        return $this->hasMany(PokerTablePlayer::class);
    }

    /**
     * @return HasMany<PokerTableSeat, $this>
     */
    public function seats(): HasMany
    {
        return $this->hasMany(PokerTableSeat::class);
    }

    /**
     * @return HasMany<PokerHand, $this>
     */
    public function hands(): HasMany
    {
        return $this->hasMany(PokerHand::class);
    }
}
