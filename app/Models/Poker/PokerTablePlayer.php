<?php

namespace App\Models\Poker;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PokerTablePlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'poker_table_id',
        'user_id',
        'is_bot',
        'bot_profile',
        'bot_difficulty',
        'nickname',
        'stack',
        'seat_number',
        'status',
        'joined_at',
        'left_at',
        'last_seen_at',
    ];

    protected $casts = [
        'poker_table_id' => 'integer',
        'user_id' => 'integer',
        'is_bot' => 'boolean',
        'stack' => 'integer',
        'seat_number' => 'integer',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<PokerTable, $this>
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(PokerTable::class, 'poker_table_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<PokerBotDecisionLog, $this>
     */
    public function botDecisionLogs(): HasMany
    {
        return $this->hasMany(PokerBotDecisionLog::class, 'poker_table_player_id');
    }
}

