<?php

namespace App\Models\Poker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PokerBotDecisionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'poker_table_id',
        'poker_table_player_id',
        'actor',
        'profile',
        'difficulty',
        'street',
        'action',
        'amount',
        'score',
        'range',
        'label',
        'board_texture',
        'has_flush_draw',
        'has_straight_draw',
        'context',
    ];

    protected $casts = [
        'poker_table_id' => 'integer',
        'poker_table_player_id' => 'integer',
        'amount' => 'integer',
        'score' => 'integer',
        'has_flush_draw' => 'boolean',
        'has_straight_draw' => 'boolean',
        'context' => 'array',
    ];

    /**
     * @return BelongsTo<PokerTable, $this>
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(PokerTable::class, 'poker_table_id');
    }

    /**
     * @return BelongsTo<PokerTablePlayer, $this>
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(PokerTablePlayer::class, 'poker_table_player_id');
    }
}
