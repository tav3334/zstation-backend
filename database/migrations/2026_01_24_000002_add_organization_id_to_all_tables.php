<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Récupérer l'ID de l'organisation par défaut
        $defaultOrgId = DB::table('organizations')->where('code', 'DEFAULT')->value('id');

        // 1. Ajouter organization_id à la table users
        if (!Schema::hasColumn('users', 'organization_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            });
        }

        // 2. Ajouter organization_id à la table machines
        if (!Schema::hasColumn('machines', 'organization_id')) {
            Schema::table('machines', function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }
        DB::table('machines')->whereNull('organization_id')->update(['organization_id' => $defaultOrgId]);

        // 3. Ajouter organization_id à la table customers
        if (!Schema::hasColumn('customers', 'organization_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }
        DB::table('customers')->whereNull('organization_id')->update(['organization_id' => $defaultOrgId]);

        // 4. Ajouter organization_id à la table game_sessions
        if (!Schema::hasColumn('game_sessions', 'organization_id')) {
            Schema::table('game_sessions', function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }
        DB::table('game_sessions')->whereNull('organization_id')->update(['organization_id' => $defaultOrgId]);

        // 5. Ajouter organization_id à la table products
        if (!Schema::hasColumn('products', 'organization_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }
        DB::table('products')->whereNull('organization_id')->update(['organization_id' => $defaultOrgId]);

        // 6. Ajouter organization_id à la table product_sales
        if (!Schema::hasColumn('product_sales', 'organization_id')) {
            Schema::table('product_sales', function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }
        DB::table('product_sales')->whereNull('organization_id')->update(['organization_id' => $defaultOrgId]);

        // 7. Ajouter organization_id à la table payments
        if (!Schema::hasColumn('payments', 'organization_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }
        DB::table('payments')->whereNull('organization_id')->update(['organization_id' => $defaultOrgId]);

        // 8. Ajouter organization_id à la table cash_registers
        if (!Schema::hasColumn('cash_registers', 'organization_id')) {
            Schema::table('cash_registers', function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }
        DB::table('cash_registers')->whereNull('organization_id')->update(['organization_id' => $defaultOrgId]);

        // 9. Ajouter organization_id à la table games
        if (!Schema::hasColumn('games', 'organization_id')) {
            Schema::table('games', function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }
        DB::table('games')->whereNull('organization_id')->update(['organization_id' => $defaultOrgId]);

        // Assigner les utilisateurs existants (sauf super_admin) à l'organisation par défaut
        DB::table('users')
            ->where('role', '!=', 'super_admin')
            ->whereNull('organization_id')
            ->update(['organization_id' => $defaultOrgId]);
    }

    public function down(): void
    {
        $tables = ['users', 'machines', 'customers', 'game_sessions', 'products', 'product_sales', 'payments', 'cash_registers', 'games'];

        foreach ($tables as $tableName) {
            if (Schema::hasColumn($tableName, 'organization_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['organization_id']);
                    $table->dropColumn('organization_id');
                });
            }
        }
    }
};
