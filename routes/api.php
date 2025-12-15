<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameSessionController;
use App\Http\Controllers\PaymentController;

// AUTH (اختياري)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ROUTES WITHOUT AUTH
Route::apiResource('machines', MachineController::class);

Route::get('sessions', [GameSessionController::class, 'index']);
Route::post('sessions/start', [GameSessionController::class, 'startSession']);
Route::post('/sessions/stop/{id}', [GameSessionController::class, 'stopSession']);

Route::post('payments', [PaymentController::class, 'store']);

Route::get('/games', fn() => \App\Models\Game::all());
Route::get('/game-pricings', fn() => \App\Models\GamePricing::all());
Route::get('/stats/today', function () {
    return [
        'total' => \App\Models\Payment::sum('amount'),
        'sessions' => \App\Models\GameSession::count()
    ];
});
Route::get('/games', [GameController::class, 'index']);
Route::get('/machines', [MachineController::class, 'index']);


Route::get('/test', function () {
    return 'OK';
});
