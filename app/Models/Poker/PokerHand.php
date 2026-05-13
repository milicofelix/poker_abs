<?php

namespace App\Models\Poker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PokerHand extends Model
{
    use HasFactory;

    protected $fillable = [
        'poker_table_id',
        'code',
        'status',
        'street',
        'pot',
        'current_bet',
        'dealer_position',
        'winner',
        'winner_label',
        'winning_hand_name',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'pot' => 'integer',
        'current_bet' => 'integer',
        'dealer_position' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<PokerTable, $this>
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(PokerTable::class, 'poker_table_id');
    }

    /**
     * @return HasMany<PokerActionLog, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(PokerActionLog::class);
    }
}
