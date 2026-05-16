<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Poker\PokerPlayController;
use App\Http\Controllers\Poker\PokerRoundActionController;
use App\Http\Controllers\Poker\PokerHandHistoryController;
use App\Http\Controllers\Poker\PokerRankingController;
use App\Http\Controllers\Poker\PokerHandShowController;
use App\Http\Controllers\Poker\PokerHandReplayController;
use App\Http\Controllers\Poker\PokerStatisticsController;
use App\Http\Controllers\Poker\PokerTablePlayController;
use App\Http\Controllers\Poker\PokerTableCreateController;
use App\Http\Controllers\Poker\PokerLobbyController;
use App\Http\Controllers\Poker\PokerTableRoundActionController;
use App\Http\Controllers\Poker\PokerTableStateController;
use App\Http\Controllers\Poker\PokerTableTurnTimeoutController;
use App\Http\Controllers\Poker\PokerTableJoinController;
use App\Http\Controllers\Poker\PokerTableSeatController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Poker\PokerTableLeaveController;
use App\Http\Controllers\Poker\PokerTableNewHandController;
use App\Http\Controllers\Poker\PokerTableBotController;

Route::get('/', fn () => redirect()->route('poker.play'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/poker', PokerPlayController::class)->name('poker.play');
Route::post('/poker/actions', PokerRoundActionController::class)->name('poker.actions');
Route::get('/poker/hands', PokerHandHistoryController::class)->name('poker.hands.index');
Route::get('/poker/ranking', PokerRankingController::class)->name('poker.ranking.index');
Route::get('/poker/hands/{hand}', PokerHandShowController::class)->name('poker.hands.show');
Route::get('/poker/hands/{hand}/replay', PokerHandReplayController::class)->name('poker.hands.replay');
Route::get('/poker/statistics', PokerStatisticsController::class)->name('poker.statistics.index');
Route::get('/poker/tables', fn () => redirect()->route('poker.lobby'))->name('poker.tables.index');
Route::get('/poker/tables/{table}', PokerTablePlayController::class)->name('poker.tables.show');
Route::post('/poker/tables', PokerTableCreateController::class)->name('poker.tables.store');
Route::get('/poker/lobby', PokerLobbyController::class)->name('poker.lobby');
Route::post('/poker/tables/{table}/actions', PokerTableRoundActionController::class)->name('poker.tables.actions');
Route::get('/poker/tables/{table}/state', PokerTableStateController::class)->name('poker.tables.state');
Route::post('/poker/tables/{table}/timeout', PokerTableTurnTimeoutController::class)->name('poker.tables.timeout');
Route::post('/poker/tables/{table}/join', PokerTableJoinController::class)->name('poker.tables.join');
Route::post('/poker/tables/{table}/seat', PokerTableSeatController::class)->name('poker.tables.seat');
Route::post('/poker/tables/{table}/leave', PokerTableLeaveController::class)->name('poker.tables.leave');
Route::post('/poker/tables/{table}/new-hand', PokerTableNewHandController::class)->name('poker.tables.new-hand');
Route::post('/poker/tables/{table}/bots', PokerTableBotController::class)->name('poker.tables.bots');