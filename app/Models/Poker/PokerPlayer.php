<?php

namespace App\Models\Poker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class PokerPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'poker_table_id',
        'seat',
        'name',
        'type',
        'stack',
        'is_active',
    ];

    protected $casts = [
        'seat' => 'integer',
        'stack' => 'integer',
        'is_active' => 'boolean',
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

    /**
     * @return HasOne<PokerTableSeat, $this>
     */
    public function tableSeat(): HasOne
    {
        return $this->hasOne(PokerTableSeat::class);
    }
}
