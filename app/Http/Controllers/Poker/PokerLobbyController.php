<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final class PokerLobbyController extends Controller
{
    public function __invoke(): Response
    {
        $tables = PokerTable::query()
            ->where('is_private', false)
            ->withCount('realPlayers')
            ->with([
                'realPlayers' => fn ($query) => $query
                    ->with('user:id,name')
                    ->orderByRaw('seat_number is null')
                    ->orderBy('seat_number')
                    ->orderBy('id'),
                'hands' => fn ($query) => $query->latest('id')->limit(1),
            ])
            ->latest('id')
            ->get()
            ->map(fn (PokerTable $table): array => $this->serializeTable($table));

        return Inertia::render('Poker/Lobby', [
            'tables' => $tables,
            'summary' => $this->summary($tables),
            'phase' => [
                'label' => 'FASE 8.1',
                'title' => 'Lobby real de mesas',
                'description' => 'Listagem real das mesas públicas com criação/entrada e acesso separado por código para mesas privadas.',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTable(PokerTable $table): array
    {
        $latestHand = $table->hands->first();
        $playersCount = (int) $table->real_players_count;
        $maxPlayers = (int) $table->max_players;
        $isFull = $playersCount >= $maxPlayers;
        $canEnter = $table->status !== 'finished' && ! $isFull;

        return [
            'id' => $table->id,
            'name' => $table->name,
            'status' => $table->status,
            'statusLabel' => $this->statusLabel($table->status),
            'statusTone' => $this->statusTone($table->status),
            'smallBlind' => $table->small_blind,
            'bigBlind' => $table->big_blind,
            'isPrivate' => (bool) $table->is_private,
            'inviteCode' => $table->invite_code,
            'inviteUrl' => $table->invite_code ? route('poker.private-tables.invite', $table->invite_code) : null,
            'maxPlayers' => $maxPlayers,
            'playersCount' => $playersCount,
            'availableSeats' => max(0, $maxPlayers - $playersCount),
            'isFull' => $isFull,
            'canEnter' => $canEnter,
            'actionLabel' => $this->actionLabel($table->status, $isFull),
            'updatedAtLabel' => $table->updated_at?->diffForHumans(),
            'latestHand' => $latestHand ? [
                'id' => $latestHand->id,
                'status' => $latestHand->status,
                'statusLabel' => $this->handStatusLabel($latestHand->status),
                'street' => $latestHand->street,
                'streetLabel' => $this->streetLabel($latestHand->street),
                'pot' => $latestHand->pot,
            ] : null,
            'players' => $table->realPlayers->map(static fn ($player): array => [
                'id' => $player->id,
                'name' => $player->nickname ?: $player->user?->name ?: 'Jogador sem nome',
                'seatNumber' => $player->seat_number,
                'status' => $player->status,
                'isBot' => $player->is_bot,
                'lastSeenAtLabel' => $player->last_seen_at?->diffForHumans(),
            ])->values(),
            'url' => route('poker.tables.show', $table),
        ];
    }

    /**
     * @param Collection<int, array<string, mixed>> $tables
     * @return array<string, int>
     */
    private function summary(Collection $tables): array
    {
        return [
            'total' => $tables->count(),
            'waiting' => $tables->where('status', 'waiting')->count(),
            'playing' => $tables->where('status', 'playing')->count(),
            'finished' => $tables->where('status', 'finished')->count(),
            'available' => $tables->where('canEnter', true)->count(),
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'waiting' => 'Aguardando jogadores',
            'playing' => 'Em andamento',
            'finished' => 'Finalizada',
            default => $status,
        };
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            'waiting' => 'emerald',
            'playing' => 'amber',
            'finished' => 'slate',
            default => 'slate',
        };
    }

    private function actionLabel(string $status, bool $isFull): string
    {
        if ($status === 'finished') {
            return 'Ver mesa finalizada';
        }

        if ($isFull) {
            return 'Assistir mesa';
        }

        return $status === 'playing' ? 'Entrar / acompanhar' : 'Entrar na mesa';
    }

    private function handStatusLabel(string $status): string
    {
        return match ($status) {
            'running' => 'Em andamento',
            'finished' => 'Finalizada',
            default => $status,
        };
    }

    private function streetLabel(string $street): string
    {
        return match ($street) {
            'pre_flop' => 'Pré-flop',
            'flop' => 'Flop',
            'turn' => 'Turn',
            'river' => 'River',
            'showdown' => 'Showdown',
            default => $street,
        };
    }
}
