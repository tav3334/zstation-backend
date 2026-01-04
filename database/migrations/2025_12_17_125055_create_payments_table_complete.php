<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer l'ancienne table si elle existe
        Schema::dropIfExists('payments');

        // Créer la nouvelle table
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->decimal('amount', 8, 2); // Montant payé
            $table->decimal('amount_given', 8, 2)->nullable(); // Montant donné par client
            $table->decimal('change_given', 8, 2)->default(0); // Monnaie rendue
            $table->string('payment_method')->default('cash'); // Toujours cash
            $table->timestamp('payment_date');
            $table->foreignId('staff_id')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index pour recherches rapides
            $table->index('payment_date');
            $table->index(['payment_date', 'payment_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};