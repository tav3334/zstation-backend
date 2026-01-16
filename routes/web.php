<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

// 🔧 Endpoint temporaire pour exécuter la migration Super Admin
Route::get('/run-super-admin-migration-temp-2026', function () {
    try {
        // Agrandir la colonne role
        DB::statement('ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT "agent"');

        // Mettre à jour Ziad en super_admin
        DB::table('users')
            ->where('email', 'Ziad@zstation.ma')
            ->update(['role' => 'super_admin']);

        // Vérifier
        $user = DB::table('users')->where('email', 'Ziad@zstation.ma')->first();

        return response()->json([
            'success' => true,
            'message' => '✅ Migration exécutée avec succès! Ziad est maintenant super_admin.',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});
