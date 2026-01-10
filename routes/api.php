<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\Api\GameSessionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ========== AUTH (Public) ==========
Route::post('/login', [AuthController::class, 'login']);

// ========== GAMES (Public - needed for session start modal) ==========
Route::get('/games', [GameController::class, 'index']);
Route::get('/game-pricings', fn() => \App\Models\GamePricing::all());

// ========== ROUTES PROTÉGÉES (Agent + Admin) ==========
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // MACHINES
    Route::get('/machines', [MachineController::class, 'index']);
    
    // SESSIONS
    Route::get('/sessions', [GameSessionController::class, 'index']);
    Route::post('/sessions/start', [GameSessionController::class, 'start']);
    Route::post('/sessions/stop/{id}', [GameSessionController::class, 'stop']);
    Route::post('/sessions/extend/{id}', [GameSessionController::class, 'extend']);
    Route::get('/sessions/status/{id}', [GameSessionController::class, 'status']);
    Route::get('/sessions/check-auto-stop', [GameSessionController::class, 'checkAutoStop']);
    
    // PAYMENTS
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/today', [PaymentController::class, 'today']);
    Route::get('/payments/stats', [PaymentController::class, 'stats']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    
    // DASHBOARD (Stats avancées)
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/payments', [DashboardController::class, 'payments']);
    Route::get('/dashboard/sessions', [DashboardController::class, 'sessions']);

    // PRODUCTS (Vente de snacks et boissons)
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products/sell', [ProductController::class, 'sell']);
    Route::get('/products/sales', [ProductController::class, 'sales']);
    Route::get('/products/low-stock', [ProductController::class, 'lowStock']);

});

// ========== ROUTES ADMIN UNIQUEMENT ==========
Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    // Gestion utilisateurs
    Route::post('/register', [AuthController::class, 'register']);

    // Supprimer paiements
    Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);

    // Gestion complète des produits (admin seulement)
    Route::get('/products/all', [ProductController::class, 'all']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::put('/products/{id}/stock', [ProductController::class, 'updateStock']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

});

// ========== TEST ==========
Route::get('/test', function () {
    return response()->json(['status' => 'OK', 'message' => 'API Working']);
});

Route::get('/test-machine-data', function () {
    $machines = \App\Models\Machine::all();
    $result = [];

    foreach ($machines as $machine) {
        $session = $machine->activeSession()->with('gamePricing', 'game')->first();
        $result[] = [
            'machine_name' => $machine->name,
            'status' => $machine->status,
            'has_active_session' => $session ? 'YES' : 'NO',
            'session_data' => $session ? [
                'id' => $session->id,
                'pricing_reference_id' => $session->pricing_reference_id,
                'has_game_pricing' => $session->gamePricing ? 'YES' : 'NO',
                'duration_minutes' => $session->gamePricing?->duration_minutes ?? 'NULL',
                'price' => $session->gamePricing?->price ?? 'NULL'
            ] : null,
            'active_session_attribute' => $machine->active_session
        ];
    }

    return response()->json($result, 200, [], JSON_PRETTY_PRINT);
});