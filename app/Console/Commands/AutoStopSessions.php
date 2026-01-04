<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GameSession;
use Carbon\Carbon;

class AutoStopSessions extends Command
{
    protected $signature = 'sessions:auto-stop';
    protected $description = 'Auto stop expired game sessions';

    public function handle()
    {
        $this->info('Auto-stop command is running...');

        $sessions = GameSession::with('gamePricing', 'machine')
    ->whereNull('ended_at')
    ->whereIn('status', ['in_session', 'active'])
    ->get();

        foreach ($sessions as $session) {
            if (!$session->gamePricing) {
                continue;
            }

            $start = Carbon::parse($session->start_time);
            $durationSeconds = $session->gamePricing->duration_minutes * 60;
            $elapsed = $start->diffInSeconds(now());

            if ($elapsed >= $durationSeconds) {

                // 🛑 STOP SESSION
                $session->ended_at = now();
$session->status = 'closed';
$session->computed_price = $session->gamePricing->price;
$session->save();
$this->info("Session {$session->id} closed with status={$session->status}");


                // 🖥️ FREE MACHINE
                $session->machine->update([
                    'status' => 'available'
                ]);

                $this->info("Session {$session->id} auto-stopped");
            }
        }

        return 0;
    }
}
