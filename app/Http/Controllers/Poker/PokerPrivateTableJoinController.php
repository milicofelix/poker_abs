<?php

namespace App\Http\Controllers\Poker;

use App\Actions\Poker\JoinPokerTableAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PokerPrivateTableJoinController extends Controller
{
    public function __invoke(Request $request, JoinPokerTableAction $joinPokerTable): RedirectResponse
    {
        $user = $request->user();

        abort_if(! $user, 401, 'É necessário estar autenticado para entrar em uma mesa privada.');

        $validated = $request->validate([
            'invite_code' => ['required', 'string', 'max:12'],
        ]);

        $code = Str::upper(trim((string) $validated['invite_code']));
        $table = PokerTable::query()
            ->where('is_private', true)
            ->where('invite_code', $code)
            ->first();

        if (! $table) {
            return back()->with('error', 'Código de mesa privada inválido ou expirado.');
        }

        try {
            $joinPokerTable->execute($table, $user);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('poker.tables.show', $table)
            ->with('success', 'Você entrou na mesa privada.');
    }
}
