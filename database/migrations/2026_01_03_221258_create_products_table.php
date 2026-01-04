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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nom du produit (ex: Popcorn petit bol, Coca-Cola)
            $table->string('category'); // snack, drink
            $table->decimal('price', 8, 2); // Prix
            $table->string('size')->nullable(); // petit, grand, etc.
            $table->integer('stock')->default(0); // Stock disponible
            $table->boolean('is_available')->default(true); // Disponible ou non
            $table->string('image')->nullable(); // URL de l'image
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
