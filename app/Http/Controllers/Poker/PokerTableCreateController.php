<?php

namespace App\Http\Controllers\Poker;

use App\Actions\Poker\JoinPokerTableAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PokerTableCreateController extends Controller
{
    public function __invoke(Request $request, JoinPokerTableAction $joinPokerTable): RedirectResponse
    {
        $user = $request->user();

        abort_if(! $user, 401, 'É necessário estar autenticado para criar uma mesa.');

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:80'],
            'is_private' => ['sometimes', 'boolean'],
        ]);

        $tableNumber = PokerTable::query()->count() + 1;
        $isPrivate = (bool) ($validated['is_private'] ?? false);

        $table = PokerTable::query()->create([
            'name' => filled($validated['name'] ?? null)
                ? trim((string) $validated['name'])
                : 'Mesa #'.$tableNumber,
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => PokerTable::DEFAULT_MAX_PLAYERS,
            'is_private' => $isPrivate,
            'invite_code' => $isPrivate ? $this->generateInviteCode() : null,
        ]);

        $joinPokerTable->execute($table, $user);

        return redirect()
            ->route('poker.tables.show', $table)
            ->with('success', $isPrivate
                ? 'Mesa privada criada. Compartilhe o código com quem você quer convidar.'
                : 'Mesa criada. Você já entrou como jogador real.');
    }

    private function generateInviteCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (PokerTable::query()->where('invite_code', $code)->exists());

        return $code;
    }
}
