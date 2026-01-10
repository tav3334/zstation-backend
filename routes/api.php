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
// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'ZStation API is running',
        'timestamp' => now(),
        'database' => DB::connection()->getDatabaseName()
    ]);
});

// Debug route to check database
Route::get('/debug/users', function () {
    try {
        $users = \App\Models\User::all();
        return response()->json([
            'success' => true,
            'count' => $users->count(),
            'users' => $users->map(function($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role
                ];
            })
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});

// Temporary route to run migrations (REMOVE IN PRODUCTION!)
Route::get('/debug/migrate', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = \Illuminate\Support\Facades\Artisan::output();

        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'TestUserSeeder',
            '--force' => true
        ]);
        $seedOutput = \Illuminate\Support\Facades\Artisan::output();

        return response()->json([
            'success' => true,
            'migrate_output' => $migrateOutput,
            'seed_output' => $seedOutput
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});

// Temporary route to reset and seed demo data (REMOVE IN PRODUCTION!)
Route::get('/debug/reset-data', function () {
    try {
        DB::beginTransaction();

        // Delete all data
        DB::table('game_pricings')->delete();
        DB::table('games')->delete();
        DB::table('products')->delete();
        DB::table('machines')->delete();
        DB::table('pricing_modes')->delete();
        DB::table('game_types')->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'All data deleted successfully'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});

// Temporary route to seed demo data (REMOVE IN PRODUCTION!)
Route::get('/debug/seed-data', function () {
    try {
        DB::beginTransaction();

        // Insert game_types (must exist before games)
        if (!\App\Models\GameType::find(1)) {
            DB::table('game_types')->insert([
                'id' => 1,
                'name' => 'PS5',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Insert pricing_modes (must exist before game_pricings)
        if (!\App\Models\PricingMode::find(1)) {
            DB::table('pricing_modes')->insert([
                'id' => 1,
                'code' => 'standard',
                'label' => 'Standard',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Insert machines
        $machines = [
            ['id' => 1, 'name' => 'PS5 - Station 1', 'status' => 'available'],
            ['id' => 2, 'name' => 'PS5 - Station 2', 'status' => 'available'],
            ['id' => 3, 'name' => 'PS5 - Station 3', 'status' => 'available'],
            ['id' => 4, 'name' => 'PS5 - Station 4', 'status' => 'available'],
            ['id' => 5, 'name' => 'PS5 - VIP 1', 'status' => 'available'],
            ['id' => 6, 'name' => 'PS5 - VIP 2', 'status' => 'available'],
        ];

        foreach ($machines as $machine) {
            \App\Models\Machine::updateOrCreate(['id' => $machine['id']], $machine);
        }

        // Insert products
        $products = [
            ['id' => 1, 'name' => 'Popcorn', 'category' => 'snack', 'price' => 3.00, 'size' => 'petit', 'stock' => 98, 'available' => 1, 'icon' => '🍿'],
            ['id' => 2, 'name' => 'Popcorn', 'category' => 'snack', 'price' => 5.00, 'size' => 'grand', 'stock' => 78, 'available' => 1, 'icon' => '🍿'],
            ['id' => 3, 'name' => 'Coca-Cola', 'category' => 'drink', 'price' => 5.00, 'size' => 'petit', 'stock' => 50, 'available' => 1, 'icon' => '🥤'],
            ['id' => 4, 'name' => 'Coca-Cola', 'category' => 'drink', 'price' => 7.00, 'size' => 'grand', 'stock' => 50, 'available' => 1, 'icon' => '🥤'],
            ['id' => 5, 'name' => 'Sprite', 'category' => 'drink', 'price' => 5.00, 'size' => 'petit', 'stock' => 49, 'available' => 1, 'icon' => '🥤'],
            ['id' => 7, 'name' => 'Eau minérale', 'category' => 'drink', 'price' => 3.00, 'size' => null, 'stock' => 100, 'available' => 1, 'icon' => '💧'],
            ['id' => 8, 'name' => 'Cafe', 'category' => 'drink', 'price' => 6.00, 'size' => null, 'stock' => 0, 'available' => 1, 'icon' => '☕'],
        ];

        foreach ($products as $product) {
            \App\Models\Product::updateOrCreate(['id' => $product['id']], $product);
        }

        // Insert games
        $games = [
            ['id' => 1, 'game_type_id' => 1, 'name' => 'FIFA 24', 'active' => 1],
            ['id' => 2, 'game_type_id' => 1, 'name' => 'Call of Duty', 'active' => 1],
            ['id' => 3, 'game_type_id' => 1, 'name' => 'Fortnite', 'active' => 1],
            ['id' => 4, 'game_type_id' => 1, 'name' => 'GTA V', 'active' => 1],
            ['id' => 5, 'game_type_id' => 1, 'name' => 'Spider-Man', 'active' => 1],
            ['id' => 6, 'game_type_id' => 1, 'name' => 'God of War', 'active' => 1],
        ];

        foreach ($games as $game) {
            if (!\App\Models\Game::find($game['id'])) {
                DB::table('games')->insert([
                    'id' => $game['id'],
                    'game_type_id' => $game['game_type_id'],
                    'name' => $game['name'],
                    'active' => $game['active'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // Insert game pricings
        $pricings = [
            ['id' => 2, 'game_id' => 1, 'pricing_mode_id' => 1, 'duration_minutes' => 30, 'price' => 10.00],
            ['id' => 3, 'game_id' => 1, 'pricing_mode_id' => 1, 'duration_minutes' => 60, 'price' => 20.00],
            ['id' => 4, 'game_id' => 1, 'pricing_mode_id' => 1, 'duration_minutes' => 120, 'price' => 45.00],
            ['id' => 5, 'game_id' => 1, 'pricing_mode_id' => 1, 'duration_minutes' => 180, 'price' => 60.00],
            ['id' => 6, 'game_id' => 2, 'pricing_mode_id' => 1, 'duration_minutes' => 30, 'price' => 10.00],
            ['id' => 7, 'game_id' => 2, 'pricing_mode_id' => 1, 'duration_minutes' => 60, 'price' => 20.00],
            ['id' => 8, 'game_id' => 2, 'pricing_mode_id' => 1, 'duration_minutes' => 120, 'price' => 45.00],
            ['id' => 9, 'game_id' => 2, 'pricing_mode_id' => 1, 'duration_minutes' => 180, 'price' => 60.00],
            ['id' => 10, 'game_id' => 3, 'pricing_mode_id' => 1, 'duration_minutes' => 30, 'price' => 10.00],
            ['id' => 11, 'game_id' => 3, 'pricing_mode_id' => 1, 'duration_minutes' => 60, 'price' => 20.00],
            ['id' => 12, 'game_id' => 3, 'pricing_mode_id' => 1, 'duration_minutes' => 120, 'price' => 45.00],
            ['id' => 13, 'game_id' => 3, 'pricing_mode_id' => 1, 'duration_minutes' => 180, 'price' => 60.00],
            ['id' => 14, 'game_id' => 4, 'pricing_mode_id' => 1, 'duration_minutes' => 30, 'price' => 10.00],
            ['id' => 15, 'game_id' => 4, 'pricing_mode_id' => 1, 'duration_minutes' => 60, 'price' => 20.00],
            ['id' => 16, 'game_id' => 4, 'pricing_mode_id' => 1, 'duration_minutes' => 120, 'price' => 45.00],
            ['id' => 17, 'game_id' => 4, 'pricing_mode_id' => 1, 'duration_minutes' => 180, 'price' => 60.00],
            ['id' => 18, 'game_id' => 5, 'pricing_mode_id' => 1, 'duration_minutes' => 30, 'price' => 10.00],
            ['id' => 19, 'game_id' => 5, 'pricing_mode_id' => 1, 'duration_minutes' => 60, 'price' => 20.00],
            ['id' => 20, 'game_id' => 5, 'pricing_mode_id' => 1, 'duration_minutes' => 120, 'price' => 45.00],
            ['id' => 21, 'game_id' => 5, 'pricing_mode_id' => 1, 'duration_minutes' => 180, 'price' => 60.00],
            ['id' => 22, 'game_id' => 6, 'pricing_mode_id' => 1, 'duration_minutes' => 30, 'price' => 10.00],
            ['id' => 23, 'game_id' => 6, 'pricing_mode_id' => 1, 'duration_minutes' => 60, 'price' => 20.00],
            ['id' => 24, 'game_id' => 6, 'pricing_mode_id' => 1, 'duration_minutes' => 120, 'price' => 45.00],
            ['id' => 25, 'game_id' => 6, 'pricing_mode_id' => 1, 'duration_minutes' => 180, 'price' => 60.00],
            ['id' => 26, 'game_id' => 1, 'pricing_mode_id' => 1, 'duration_minutes' => 6, 'price' => 6.00],
            ['id' => 27, 'game_id' => 2, 'pricing_mode_id' => 1, 'duration_minutes' => 6, 'price' => 6.00],
            ['id' => 28, 'game_id' => 3, 'pricing_mode_id' => 1, 'duration_minutes' => 6, 'price' => 6.00],
            ['id' => 29, 'game_id' => 4, 'pricing_mode_id' => 1, 'duration_minutes' => 6, 'price' => 6.00],
            ['id' => 30, 'game_id' => 5, 'pricing_mode_id' => 1, 'duration_minutes' => 6, 'price' => 6.00],
            ['id' => 31, 'game_id' => 6, 'pricing_mode_id' => 1, 'duration_minutes' => 6, 'price' => 6.00],
        ];

        foreach ($pricings as $pricing) {
            \App\Models\GamePricing::updateOrCreate(['id' => $pricing['id']], $pricing);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Demo data seeded successfully',
            'counts' => [
                'game_types' => 1,
                'pricing_modes' => 1,
                'machines' => count($machines),
                'products' => count($products),
                'games' => count($games),
                'pricings' => count($pricings)
            ]
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});
