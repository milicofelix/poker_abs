<?php

namespace App\Http\Controllers\Poker;

use App\Actions\Poker\JoinPokerTableAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PokerPrivateTableInviteController extends Controller
{
    public function __invoke(Request $request, string $inviteCode, JoinPokerTableAction $joinPokerTable): RedirectResponse
    {
        $user = $request->user();
        $code = Str::upper(trim($inviteCode));

        $table = PokerTable::query()
            ->where('is_private', true)
            ->where('invite_code', $code)
            ->firstOrFail();

        if (! $user) {
            return redirect()
                ->route('login')
                ->with('error', 'Faça login para entrar na mesa privada pelo convite.');
        }

        try {
            $joinPokerTable->execute($table, $user);
        } catch (DomainException $exception) {
            return redirect()
                ->route('poker.lobby')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('poker.tables.show', $table)
            ->with('success', 'Convite aceito. Você entrou na mesa privada.');
    }
}
