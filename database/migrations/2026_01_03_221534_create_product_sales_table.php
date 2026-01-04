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
        Schema::create('product_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->nullable()->constrained('users')->onDelete('set null'); // Agent qui a vendu
            $table->integer('quantity'); // Quantité vendue
            $table->decimal('unit_price', 8, 2); // Prix unitaire au moment de la vente
            $table->decimal('total_price', 8, 2); // Prix total (quantity * unit_price)
            $table->string('payment_method')->default('cash'); // cash, card, mobile
            $table->timestamp('sale_date'); // Date de la vente
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_sales');
    }
};
