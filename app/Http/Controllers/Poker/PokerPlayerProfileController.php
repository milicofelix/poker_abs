<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Poker\PokerPlayerProfileService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PokerPlayerProfileController extends Controller
{
    public function show(User $user, Request $request, PokerPlayerProfileService $profile): Response
    {
        return Inertia::render('Poker/Profile', [
            'profile' => $profile->profile($user, (string) $request->query('advanced_period', '30d')),
        ]);
    }

    public function current(Request $request, PokerPlayerProfileService $profile): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Poker/Profile', [
            'profile' => $profile->profile($user, (string) $request->query('advanced_period', '30d')),
        ]);
    }
}
