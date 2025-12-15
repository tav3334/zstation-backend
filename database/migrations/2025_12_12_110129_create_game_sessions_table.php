<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
    $table->id();

    $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
    $table->foreignId('game_id')->constrained()->cascadeOnDelete();
    $table->foreignId('pricing_mode_id')->constrained();
    $table->foreignId('pricing_reference_id')->constrained('game_pricings');

    $table->foreignId('customer_id')->nullable();

    $table->timestamp('start_time')->nullable();
    $table->timestamp('end_time')->nullable();

    $table->string('status')->default('active');
    $table->decimal('computed_price', 8, 2)->nullable();

    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
