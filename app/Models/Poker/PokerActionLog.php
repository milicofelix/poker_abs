<?php

namespace App\Models\Poker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PokerActionLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'poker_hand_id',
        'poker_player_id',
        'street',
        'action',
        'amount',
        'pot_after_action',
        'metadata',
        'acted_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'pot_after_action' => 'integer',
        'metadata' => 'array',
        'acted_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<PokerHand, $this>
     */
    public function hand(): BelongsTo
    {
        return $this->belongsTo(PokerHand::class, 'poker_hand_id');
    }

    /**
     * @return BelongsTo<PokerPlayer, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(PokerPlayer::class, 'poker_player_id');
    }
}
