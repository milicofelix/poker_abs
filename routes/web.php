<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Poker\PokerPlayController;
use App\Http\Controllers\Poker\PokerRoundActionController;

Route::get('/', fn () => redirect()->route('poker.play'));

Route::get('/poker', PokerPlayController::class)->name('poker.play');
Route::post('/poker/actions', PokerRoundActionController::class)->name('poker.actions');