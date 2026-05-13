<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Poker\PokerPlayController;
use App\Http\Controllers\Poker\PokerRoundActionController;
use App\Http\Controllers\Poker\PokerHandHistoryController;
use App\Http\Controllers\Poker\PokerRankingController;
use App\Http\Controllers\Poker\PokerHandShowController;
use App\Http\Controllers\Poker\PokerHandReplayController;
use App\Http\Controllers\Poker\PokerStatisticsController;

Route::get('/', fn () => redirect()->route('poker.play'));

Route::get('/poker', PokerPlayController::class)->name('poker.play');
Route::post('/poker/actions', PokerRoundActionController::class)->name('poker.actions');
Route::get('/poker/hands', PokerHandHistoryController::class)->name('poker.hands.index');
Route::get('/poker/ranking', PokerRankingController::class)->name('poker.ranking.index');
Route::get('/poker/hands/{hand}', PokerHandShowController::class)->name('poker.hands.show');
Route::get('/poker/hands/{hand}/replay', PokerHandReplayController::class)->name('poker.hands.replay');
Route::get('/poker/statistics', PokerStatisticsController::class)->name('poker.statistics.index');