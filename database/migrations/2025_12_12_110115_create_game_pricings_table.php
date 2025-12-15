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
        Schema::create('game_pricings', function (Blueprint $table) {
    $table->id();

    $table->foreignId('game_id')
          ->constrained('games')
          ->onDelete('cascade');

    $table->foreignId('pricing_mode_id')
          ->constrained('pricing_modes')
          ->onDelete('cascade');

    $table->integer('duration_minutes'); // 6 / 30 / 60
    $table->decimal('price', 8, 2);

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_pricings');
    }
};
