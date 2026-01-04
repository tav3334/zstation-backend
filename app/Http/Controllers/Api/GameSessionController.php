<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GameSession;
use App\Models\Machine;
use App\Models\GamePricing;

class GameSessionController extends Controller
{
    public function index()
    {
        $sessions = GameSession::with(['machine', 'game', 'gamePricing'])
            ->orderBy('start_time', 'desc')
            ->get();

        return response()->json($sessions);
    }

    public function start(Request $request)
    {
        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'game_id' => 'required|exists:games,id',
            'game_pricing_id' => 'required|exists:game_pricings,id',
        ]);

        $machine = Machine::findOrFail($request->machine_id);

        // ❌ Vérifier si machine déjà occupée
        if ($machine->activeSession()->exists()) {
            return response()->json([
                'message' => 'Machine already in session'
            ], 409);
        }

        // ✅ IMPORTANT: start_time doit TOUJOURS être now()
        $startTime = now();

        // ✅ Créer la session
        $session = GameSession::create([
            'machine_id' => $machine->id,
            'game_id' => $request->game_id,
            'pricing_reference_id' => $request->game_pricing_id,
            'pricing_mode_id' => 1,
            'customer_id' => null,
            'start_time' => $startTime,
            'status' => 'active'
        ]);

        // ✅ Mettre à jour le statut de la machine
        $machine->update(['status' => 'in_session']);

        return response()->json([
            'message' => 'Session started',
            'session' => $session->load(['game', 'gamePricing']),
            'start_time' => $startTime->toISOString(),
            'will_auto_stop_at' => $startTime->addMinutes($session->gamePricing->duration_minutes)->toISOString()
        ], 201);
    }

    public function stop($id)
    {
        $session = GameSession::with(['gamePricing', 'machine', 'game'])->findOrFail($id);

        if ($session->ended_at) {
            return response()->json([
                'message' => 'Session already stopped'
            ], 409);
        }

        // ⏱️ Calculer la durée réelle
        $session->ended_at = now();
        $durationMinutes = now()->diffInMinutes($session->start_time);

        // 💰 TARIF FORFAITAIRE (prix complet)
        $session->computed_price = $session->gamePricing->price;
        $session->status = 'completed';
        $session->save();

        // ✅ Libérer la machine
        $session->machine->update(['status' => 'available']);

        return response()->json([
            'message' => 'Session stopped successfully',
            'session' => $session->load(['machine', 'game', 'gamePricing']),
            'price' => $session->computed_price,
            'duration_used' => $durationMinutes . ' min',
            'duration_paid' => $session->gamePricing->duration_minutes . ' min',
            'forfait' => $session->gamePricing->duration_minutes . ' min = ' . $session->gamePricing->price . ' DH',
            'payment_ready' => true // Indique que le paiement peut être enregistré
        ]);
    }

    // 🔄 PROLONGATION
    public function extend(Request $request, $id)
    {
        $request->validate([
            'game_pricing_id' => 'required|exists:game_pricings,id',
        ]);

        $session = GameSession::with('gamePricing')->findOrFail($id);

        if ($session->ended_at) {
            return response()->json([
                'message' => 'Cannot extend completed session'
            ], 409);
        }

        $newPricing = GamePricing::findOrFail($request->game_pricing_id);

        // Créer une nouvelle session liée
        $extendedSession = GameSession::create([
            'machine_id' => $session->machine_id,
            'game_id' => $session->game_id,
            'pricing_reference_id' => $newPricing->id,
            'pricing_mode_id' => 1,
            'customer_id' => $session->customer_id,
            'start_time' => now(),
            'status' => 'active'
        ]);

        // Marquer l'ancienne session comme terminée
        $session->update([
            'ended_at' => now(),
            'computed_price' => $session->gamePricing->price,
            'status' => 'completed'
        ]);

        return response()->json([
            'message' => 'Session extended successfully',
            'old_session' => $session,
            'new_session' => $extendedSession->load(['game', 'gamePricing']),
            'total_paid' => $session->computed_price + $newPricing->price,
            'will_auto_stop_at' => now()->addMinutes($newPricing->duration_minutes)->toISOString()
        ]);
    }

    // ⏰ AUTO-STOP
    public function checkAutoStop()
    {
        $sessions = GameSession::whereNull('ended_at')
            ->where('status', 'active')
            ->with('gamePricing', 'machine')
            ->get();

        $stoppedSessions = [];
        $debugInfo = [];

        foreach ($sessions as $session) {
            $durationSeconds = $session->gamePricing->duration_minutes * 60;
            $elapsed = now()->diffInSeconds($session->start_time);

            // Debug info
            $debugInfo[] = [
                'session_id' => $session->id,
                'machine' => $session->machine->name,
                'start_time' => $session->start_time->toDateTimeString(),
                'elapsed_seconds' => $elapsed,
                'duration_seconds' => $durationSeconds,
                'should_stop' => $elapsed >= $durationSeconds
            ];

            // Si le temps est écoulé, arrêter automatiquement
            if ($elapsed >= $durationSeconds) {
                $this->stopSessionInternal($session);
                $stoppedSessions[] = [
                    'session_id' => $session->id,
                    'machine' => $session->machine->name,
                    'duration' => $session->gamePricing->duration_minutes . ' min',
                    'price' => $session->computed_price . ' DH',
                    'elapsed' => round($elapsed / 60, 1) . ' min'
                ];
            }
        }

        return response()->json([
            'status' => 'checked',
            'total_active_sessions' => $sessions->count(),
            'stopped_count' => count($stoppedSessions),
            'stopped_sessions' => $stoppedSessions,
            'debug' => $debugInfo
        ]);
    }

    // 📊 Statut de session (temps restant)
    public function status($id)
    {
        $session = GameSession::with(['gamePricing', 'machine'])->findOrFail($id);

        if ($session->ended_at) {
            return response()->json([
                'status' => 'completed',
                'message' => 'Session terminée'
            ]);
        }

        $elapsedSeconds = now()->diffInSeconds($session->start_time);
        $totalSeconds = $session->gamePricing->duration_minutes * 60;
        $remainingSeconds = max(0, $totalSeconds - $elapsedSeconds);

        $elapsedMinutes = floor($elapsedSeconds / 60);
        $remainingMinutes = floor($remainingSeconds / 60);

        return response()->json([
            'status' => 'active',
            'elapsed_seconds' => $elapsedSeconds,
            'elapsed_minutes' => $elapsedMinutes,
            'remaining_seconds' => $remainingSeconds,
            'remaining_minutes' => $remainingMinutes,
            'will_auto_stop' => $remainingSeconds <= 0,
            'forfait' => $session->gamePricing->duration_minutes . ' min = ' . $session->gamePricing->price . ' DH',
            'machine' => $session->machine->name
        ]);
    }

    private function stopSessionInternal(GameSession $session)
    {
        if ($session->ended_at) return;

        $session->ended_at = now();
        $session->computed_price = $session->gamePricing->price;
        $session->status = 'completed';
        $session->save();

        $session->machine->update(['status' => 'available']);
    }
}