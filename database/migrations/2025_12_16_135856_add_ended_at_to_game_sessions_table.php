<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->timestamp('ended_at')->nullable()->after('start_time');
        });
    }

    public function down()
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn('ended_at');
        });
    }
};
