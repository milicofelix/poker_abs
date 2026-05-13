<?php

namespace App\Models\Poker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PokerTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'small_blind',
        'big_blind',
        'max_players',
    ];

    protected $casts = [
        'small_blind' => 'integer',
        'big_blind' => 'integer',
        'max_players' => 'integer',
    ];

    /**
     * @return HasMany<PokerPlayer, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(PokerPlayer::class);
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
