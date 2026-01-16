<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Agrandir la colonne role pour supporter 'super_admin'
            $table->string('role', 50)->default('agent')->change();
        });

        // Mettre à jour l'utilisateur Ziad en super_admin
        DB::table('users')
            ->where('email', 'Ziad@zstation.ma')
            ->update(['role' => 'super_admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('agent')->change();
        });
    }
};
