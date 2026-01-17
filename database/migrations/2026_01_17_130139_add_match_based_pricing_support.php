<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'per_match' pricing mode
        DB::table('pricing_modes')->insert([
            'code' => 'per_match',
            'label' => 'Par Match',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Modify game_pricings table
        Schema::table('game_pricings', function (Blueprint $table) {
            $table->integer('duration_minutes')->nullable()->change();
            $table->integer('matches_count')->nullable()->after('duration_minutes');
        });

        // Modify game_sessions table
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->integer('matches_played')->nullable()->after('ended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'per_match' pricing mode
        DB::table('pricing_modes')->where('code', 'per_match')->delete();

        // Revert game_pricings table
        Schema::table('game_pricings', function (Blueprint $table) {
            $table->dropColumn('matches_count');
            $table->integer('duration_minutes')->nullable(false)->change();
        });

        // Revert game_sessions table
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn('matches_played');
        });
    }
};
