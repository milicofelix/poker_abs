<?php

namespace App\Models\Poker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PokerTableSeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'poker_table_id',
        'poker_player_id',
        'seat_number',
        'status',
        'role',
        'stack_snapshot',
        'is_dealer',
        'is_small_blind',
        'is_big_blind',
    ];

    protected $casts = [
        'poker_table_id' => 'integer',
        'poker_player_id' => 'integer',
        'seat_number' => 'integer',
        'stack_snapshot' => 'integer',
        'is_dealer' => 'boolean',
        'is_small_blind' => 'boolean',
        'is_big_blind' => 'boolean',
    ];

    /**
     * @return BelongsTo<PokerTable, $this>
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(PokerTable::class, 'poker_table_id');
    }

    /**
     * @return BelongsTo<PokerPlayer, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(PokerPlayer::class, 'poker_player_id');
    }
}
