<?php

use Illuminate\Support\Facades\Schedule;

// ⏰ Auto-stop toutes les minutes
Schedule::call(function () {
    $controller = app(\App\Http\Controllers\Api\GameSessionController::class);
    $result = $controller->checkAutoStop();
    
    $data = $result->getData();
    if ($data->stopped_count > 0) {
        \Log::info("Auto-stop: {$data->stopped_count} sessions arrêtées", [
            'sessions' => $data->stopped_sessions
        ]);
    }
})->everyMinute()->name('auto-stop-sessions');