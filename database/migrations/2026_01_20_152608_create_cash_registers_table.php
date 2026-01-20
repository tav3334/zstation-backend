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
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->decimal('opening_balance', 10, 2)->default(0); // Fond de caisse initial
            $table->decimal('total_cash_in', 10, 2)->default(0); // Total cash reçu (sessions + produits)
            $table->decimal('total_change_out', 10, 2)->default(0); // Total monnaie rendue
            $table->decimal('closing_balance', 10, 2)->nullable(); // Solde de fermeture
            $table->decimal('withdrawn_amount', 10, 2)->default(0); // Montant retiré par le boss
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
