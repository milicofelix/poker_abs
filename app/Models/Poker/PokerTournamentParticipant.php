<?php

namespace App\Models\Poker;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PokerTournamentParticipant extends Model
{
    use HasFactory;

    public const STATUS_REGISTERED = 'registered';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ELIMINATED = 'eliminated';
    public const STATUS_WINNER = 'winner';

    protected $fillable = [
        'poker_tournament_id',
        'user_id',
        'status',
        'starting_stack',
        'current_stack',
        'finish_position',
        'prize_amount',
        'reentries_count',
        'addons_count',
        'registered_at',
        'reentered_at',
        'addon_taken_at',
        'eliminated_at',
    ];

    protected $casts = [
        'poker_tournament_id' => 'integer',
        'user_id' => 'integer',
        'starting_stack' => 'integer',
        'current_stack' => 'integer',
        'finish_position' => 'integer',
        'prize_amount' => 'integer',
        'reentries_count' => 'integer',
        'addons_count' => 'integer',
        'registered_at' => 'datetime',
        'reentered_at' => 'datetime',
        'addon_taken_at' => 'datetime',
        'eliminated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<PokerTournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(PokerTournament::class, 'poker_tournament_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
