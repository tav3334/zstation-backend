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
// Rate limiting: 5 login attempts per minute
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// ========== GAMES (Public - needed for session start modal) ==========
Route::get('/games', [GameController::class, 'index']);
Route::get('/game-pricings', fn() => \App\Models\GamePricing::all());

// ========== ROUTES PROTÉGÉES (Agent + Admin) ==========
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

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

// ========== HEALTH CHECK ==========
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'ZStation API is running',
        'timestamp' => now()
    ]);
});
