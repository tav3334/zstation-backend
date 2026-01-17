<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\Api\GameSessionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\TempMigrationController;
use App\Http\Controllers\FixSessionsController;
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

// ========== TEMPORARY FIX ENDPOINT ==========
Route::get('/fix/stop-all-sessions', [FixSessionsController::class, 'stopAllSessions']);

// ========== DEBUG ENDPOINT ==========
Route::get('/debug/machine-test', function () {
    try {
        $machines = \App\Models\Machine::all();
        return response()->json([
            'success' => true,
            'machines_count' => $machines->count(),
            'machines' => $machines
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/debug/machines-controller', function () {
    try {
        $controller = new \App\Http\Controllers\Api\MachineController();
        return $controller->index();
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => explode("\n", $e->getTraceAsString())
        ], 500);
    }
});


// ========== ROUTES SUPER ADMIN UNIQUEMENT ==========
Route::middleware(['auth:sanctum', 'role:super_admin'])->prefix('super-admin')->group(function () {

    // Gestion des Utilisateurs
    Route::get('/users', [\App\Http\Controllers\SuperAdmin\UserController::class, 'index']);
    Route::post('/users', [\App\Http\Controllers\SuperAdmin\UserController::class, 'store']);
    Route::get('/users/{id}', [\App\Http\Controllers\SuperAdmin\UserController::class, 'show']);
    Route::put('/users/{id}', [\App\Http\Controllers\SuperAdmin\UserController::class, 'update']);
    Route::delete('/users/{id}', [\App\Http\Controllers\SuperAdmin\UserController::class, 'destroy']);

    // Gestion des Machines
    Route::get('/machines', [\App\Http\Controllers\SuperAdmin\MachineController::class, 'index']);
    Route::post('/machines', [\App\Http\Controllers\SuperAdmin\MachineController::class, 'store']);
    Route::get('/machines/{id}', [\App\Http\Controllers\SuperAdmin\MachineController::class, 'show']);
    Route::put('/machines/{id}', [\App\Http\Controllers\SuperAdmin\MachineController::class, 'update']);
    Route::delete('/machines/{id}', [\App\Http\Controllers\SuperAdmin\MachineController::class, 'destroy']);

    // Gestion des Jeux
    Route::get('/games', [\App\Http\Controllers\SuperAdmin\GameController::class, 'index']);
    Route::post('/games', [\App\Http\Controllers\SuperAdmin\GameController::class, 'store']);
    Route::get('/games/{id}', [\App\Http\Controllers\SuperAdmin\GameController::class, 'show']);
    Route::put('/games/{id}', [\App\Http\Controllers\SuperAdmin\GameController::class, 'update']);
    Route::delete('/games/{id}', [\App\Http\Controllers\SuperAdmin\GameController::class, 'destroy']);

    // Gestion des Tarifs
    Route::post('/games/{id}/pricings', [\App\Http\Controllers\SuperAdmin\GameController::class, 'addPricing']);
    Route::put('/games/{gameId}/pricings/{pricingId}', [\App\Http\Controllers\SuperAdmin\GameController::class, 'updatePricing']);
    Route::delete('/games/{gameId}/pricings/{pricingId}', [\App\Http\Controllers\SuperAdmin\GameController::class, 'deletePricing']);

    // Récupérer les modes de tarification disponibles
    Route::get('/pricing-modes', function () {
        return response()->json([
            'success' => true,
            'pricing_modes' => \App\Models\PricingMode::all()
        ]);
    });
});
// 🔧 Endpoint temporaire pour créer l'admin Ziad
Route::get('/create-admin-ziad-temp', function () {
    try {
        $user = DB::table('users')->where('email', 'Ziad@zstation.ma')->first();

        if ($user) {
            return response()->json([
                'success' => false,
                'message' => 'Un utilisateur avec cet email existe déjà',
                'existing_user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ], 400);
        }

        DB::table('users')->insert([
            'name' => 'Ziad',
            'email' => 'Ziad@zstation.ma',
            'password' => bcrypt('latifa2026'),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $newUser = DB::table('users')->where('email', 'Ziad@zstation.ma')->first();

        return response()->json([
            'success' => true,
            'message' => '✅ Compte admin Ziad créé avec succès!',
            'user' => [
                'id' => $newUser->id,
                'name' => $newUser->name,
                'email' => $newUser->email,
                'role' => $newUser->role
            ],
            'credentials' => [
                'email' => 'Ziad@zstation.ma',
                'password' => 'latifa2026'
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});


// ========== TEMPORARY MIGRATION ENDPOINT (DELETE AFTER USE) ==========
Route::get("/temp/migrate-match-pricing", [TempMigrationController::class, "executeMatchPricingMigration"]);
