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
use App\Http\Controllers\CashRegisterController;
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

    // CAISSE (Fond de caisse)
    Route::get('/cash-register/today', [CashRegisterController::class, 'today']);
    Route::get('/cash-register/history', [CashRegisterController::class, 'history']);

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

    // Gestion de la caisse (admin seulement)
    Route::post('/cash-register/opening-balance', [CashRegisterController::class, 'setOpeningBalance']);
    Route::post('/cash-register/withdraw', [CashRegisterController::class, 'withdraw']);
    Route::post('/cash-register/close', [CashRegisterController::class, 'close']);

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

    // Gestion des Organisations
    Route::get('/organizations', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'index']);
    Route::post('/organizations', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'store']);
    Route::get('/organizations/{id}', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'show']);
    Route::put('/organizations/{id}', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'update']);
    Route::delete('/organizations/{id}', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'destroy']);
    Route::post('/organizations/{id}/assign-user', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'assignUser']);
    Route::post('/organizations/{id}/remove-user', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'removeUser']);
    Route::get('/organizations/{id}/users', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'users']);
    Route::get('/organizations/{id}/stats', [\App\Http\Controllers\SuperAdmin\OrganizationController::class, 'stats']);

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

    // Statistiques du Dashboard Super Admin (gestion du système)
    Route::get('/stats', function () {
        // === UTILISATEURS ===
        $totalUsers = \App\Models\User::count();
        $usersByRole = \App\Models\User::select('role', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();
        $recentUsers = \App\Models\User::where('created_at', '>=', now()->subDays(7))->count();

        // === MACHINES ===
        $totalMachines = \App\Models\Machine::count();
        $machinesByStatus = \App\Models\Machine::select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // === JEUX ===
        $totalGames = \App\Models\Game::count();
        $activeGames = \App\Models\Game::where('active', true)->count();
        $inactiveGames = \App\Models\Game::where('active', false)->count();
        $gamesByType = \App\Models\Game::with('type')
            ->get()
            ->groupBy(function ($game) {
                return $game->type->name ?? 'Non catégorisé';
            })
            ->map(function ($games) {
                return $games->count();
            })
            ->toArray();

        // === TARIFS ===
        $totalPricings = \App\Models\GamePricing::count();
        $gamesWithPricings = \App\Models\Game::has('pricings')->count();
        $gamesWithoutPricings = \App\Models\Game::doesntHave('pricings')->count();

        // === TYPES DE JEUX ===
        $totalGameTypes = \App\Models\GameType::count();

        // === PRODUITS ===
        $totalProducts = \App\Models\Product::count();
        $productsAvailable = \App\Models\Product::where('is_available', true)->count();
        $productsOutOfStock = \App\Models\Product::where('stock', '<=', 0)->count();
        $productsLowStock = \App\Models\Product::where('stock', '>', 0)->where('stock', '<=', 5)->count();
        $productsByCategory = \App\Models\Product::select('category', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        return response()->json([
            'success' => true,
            'stats' => [
                // Utilisateurs
                'users' => [
                    'total' => $totalUsers,
                    'by_role' => $usersByRole,
                    'recent_7_days' => $recentUsers,
                ],
                // Machines
                'machines' => [
                    'total' => $totalMachines,
                    'by_status' => $machinesByStatus,
                ],
                // Jeux
                'games' => [
                    'total' => $totalGames,
                    'active' => $activeGames,
                    'inactive' => $inactiveGames,
                    'by_type' => $gamesByType,
                ],
                // Tarifs
                'pricings' => [
                    'total' => $totalPricings,
                    'games_with_pricings' => $gamesWithPricings,
                    'games_without_pricings' => $gamesWithoutPricings,
                ],
                // Types de jeux
                'game_types' => [
                    'total' => $totalGameTypes,
                ],
                // Produits
                'products' => [
                    'total' => $totalProducts,
                    'available' => $productsAvailable,
                    'out_of_stock' => $productsOutOfStock,
                    'low_stock' => $productsLowStock,
                    'by_category' => $productsByCategory,
                ],
            ]
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

// 🔧 Debug endpoint for cash register - test full today() logic
Route::get('/debug/cash-register', function () {
    try {
        $today = \Carbon\Carbon::today()->toDateString();
        $register = \App\Models\CashRegister::where('date', $today)->first();

        if (!$register) {
            return response()->json([
                'success' => true,
                'message' => 'No register for today, would create new one'
            ]);
        }

        // Test formatRegister logic step by step
        $openingBalance = (float) $register->opening_balance;
        $totalCashIn = (float) $register->total_cash_in;
        $totalChangeOut = (float) $register->total_change_out;
        $withdrawnAmount = (float) $register->withdrawn_amount;

        $currentBalance = $openingBalance + $totalCashIn - $totalChangeOut - $withdrawnAmount;
        $netProfit = $totalCashIn - $totalChangeOut;

        // Test date formatting
        $dateVal = $register->date;
        $dateType = gettype($dateVal);
        $dateClass = is_object($dateVal) ? get_class($dateVal) : 'not an object';

        return response()->json([
            'success' => true,
            'register_id' => $register->id,
            'date_value' => $dateVal,
            'date_type' => $dateType,
            'date_class' => $dateClass,
            'opening_balance' => $openingBalance,
            'total_cash_in' => $totalCashIn,
            'total_change_out' => $totalChangeOut,
            'withdrawn_amount' => $withdrawnAmount,
            'current_balance' => $currentBalance,
            'net_profit' => $netProfit,
            'opened_at_raw' => $register->getAttributes()['opened_at'] ?? null,
            'closed_at_raw' => $register->getAttributes()['closed_at'] ?? null,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 10)
        ], 500);
    }
});

// 🔧 Temporary endpoint to create cash_registers table
Route::get('/temp/migrate-cash-register', function () {
    try {
        // Check if table already exists
        if (\Illuminate\Support\Facades\Schema::hasTable('cash_registers')) {
            return response()->json([
                'success' => true,
                'message' => 'Table cash_registers already exists',
                'action' => 'skipped'
            ]);
        }

        // Create the table
        \Illuminate\Support\Facades\Schema::create('cash_registers', function ($table) {
            $table->id();
            $table->date('date')->unique();
            $table->decimal('opening_balance', 10, 2)->default(0);
            $table->decimal('total_cash_in', 10, 2)->default(0);
            $table->decimal('total_change_out', 10, 2)->default(0);
            $table->decimal('closing_balance', 10, 2)->nullable();
            $table->decimal('withdrawn_amount', 10, 2)->default(0);
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        return response()->json([
            'success' => true,
            'message' => 'Table cash_registers created successfully!',
            'action' => 'created'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// 🔧 Debug endpoint - test FULL today() controller logic
Route::get('/debug/cash-register-full', function () {
    try {
        $today = \Carbon\Carbon::today()->toDateString();
        $register = \App\Models\CashRegister::where('date', $today)->first();

        if (!$register) {
            return response()->json([
                'step' => 'no_register',
                'message' => 'No register found'
            ]);
        }

        // Test syncWithPayments logic
        $date = $register->date;

        // Check if Payment model works (amount_given = montant donné par le client)
        $sessionsCash = \App\Models\Payment::whereDate('created_at', $date)->sum('amount_given');
        $sessionsChange = \App\Models\Payment::whereDate('created_at', $date)->sum('change_given');

        // Check if ProductSale model exists and works
        $productsCash = 0;
        try {
            $productsCash = \App\Models\ProductSale::whereDate('sale_date', $date)
                ->where('payment_method', 'cash')
                ->sum('total_price');
        } catch (\Exception $e) {
            return response()->json([
                'step' => 'product_sale_error',
                'error' => $e->getMessage()
            ], 500);
        }

        // Test formatRegister
        $openingBalance = (float) $register->opening_balance;
        $totalCashIn = (float) $register->total_cash_in;
        $totalChangeOut = (float) $register->total_change_out;
        $withdrawnAmount = (float) $register->withdrawn_amount;
        $closingBalance = $register->closing_balance !== null ? (float) $register->closing_balance : null;

        $currentBalance = $openingBalance + $totalCashIn - $totalChangeOut - $withdrawnAmount;
        $netProfit = $totalCashIn - $totalChangeOut;
        $isOpen = $register->opened_at !== null && $register->closed_at === null;

        // Format date
        $dateStr = $register->date;
        $dateFormatted = \Carbon\Carbon::parse($dateStr)->format('d/m/Y');

        // Format times
        $openedAt = $register->opened_at ? \Carbon\Carbon::parse($register->opened_at)->format('H:i') : null;
        $closedAt = $register->closed_at ? \Carbon\Carbon::parse($register->closed_at)->format('H:i') : null;

        return response()->json([
            'success' => true,
            'step' => 'complete',
            'sync_data' => [
                'sessions_cash' => $sessionsCash,
                'sessions_change' => $sessionsChange,
                'products_cash' => $productsCash,
            ],
            'register' => [
                'id' => $register->id,
                'date' => $dateStr,
                'date_formatted' => $dateFormatted,
                'opening_balance' => $openingBalance,
                'total_cash_in' => $totalCashIn,
                'total_change_out' => $totalChangeOut,
                'withdrawn_amount' => $withdrawnAmount,
                'current_balance' => $currentBalance,
                'net_profit' => $netProfit,
                'closing_balance' => $closingBalance,
                'is_open' => $isOpen,
                'opened_at' => $openedAt,
                'closed_at' => $closedAt,
                'notes' => $register->notes,
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ], 500);
    }
});
