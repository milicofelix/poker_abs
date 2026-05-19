<?php

namespace App\Models\Poker;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PokerBankrollTransaction extends Model
{
    use HasFactory;

    public const TYPE_BUY_IN = 'buy_in';
    public const TYPE_PAYOUT = 'payout';

    protected $fillable = [
        'user_id',
        'poker_table_id',
        'poker_hand_id',
        'poker_table_player_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'metadata',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'poker_table_id' => 'integer',
        'poker_hand_id' => 'integer',
        'poker_table_player_id' => 'integer',
        'amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PokerTable, $this>
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(PokerTable::class, 'poker_table_id');
    }

    /**
     * @return BelongsTo<PokerHand, $this>
     */
    public function hand(): BelongsTo
    {
        return $this->belongsTo(PokerHand::class, 'poker_hand_id');
    }

    /**
     * @return BelongsTo<PokerTablePlayer, $this>
     */
    public function tablePlayer(): BelongsTo
    {
        return $this->belongsTo(PokerTablePlayer::class, 'poker_table_player_id');
    }
}
