<?php

namespace App\Models\Poker;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PokerTablePlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'poker_table_id',
        'user_id',
        'nickname',
        'stack',
        'seat_number',
        'status',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'poker_table_id' => 'integer',
        'user_id' => 'integer',
        'stack' => 'integer',
        'seat_number' => 'integer',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
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
}
