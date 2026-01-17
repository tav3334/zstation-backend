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

        $gamePricing = GamePricing::with('pricingMode')->findOrFail($request->game_pricing_id);

        // ✅ IMPORTANT: start_time doit TOUJOURS être now()
        $startTime = now();

        // ✅ Créer la session
        $session = GameSession::create([
            'machine_id' => $machine->id,
            'game_id' => $request->game_id,
            'pricing_reference_id' => $request->game_pricing_id,
            'pricing_mode_id' => $gamePricing->pricing_mode_id,
            'customer_id' => null,
            'start_time' => $startTime,
            'status' => 'active'
        ]);

        // ✅ Mettre à jour le statut de la machine
        $machine->update(['status' => 'in_session']);

        $response = [
            'message' => 'Session started',
            'session' => $session->load(['game', 'gamePricing.pricingMode']),
            'start_time' => $startTime->toISOString(),
            'pricing_mode' => $gamePricing->pricingMode->code
        ];

        // Ajouter will_auto_stop_at seulement pour les modes basés sur le temps
        if ($gamePricing->duration_minutes !== null) {
            $response['will_auto_stop_at'] = $startTime->addMinutes($gamePricing->duration_minutes)->toISOString();
        }

        return response()->json($response, 201);
    }

    public function stop(Request $request, $id)
    {
        $session = GameSession::with(['gamePricing.pricingMode', 'machine', 'game'])->findOrFail($id);

        if ($session->ended_at) {
            return response()->json([
                'message' => 'Session already stopped'
            ], 409);
        }

        $pricingMode = $session->gamePricing->pricingMode->code;

        // Pour le mode "par match", le nombre de matchs est requis
        if ($pricingMode === 'per_match') {
            $request->validate([
                'matches_played' => 'required|integer|min:1'
            ]);
        }

        // ⏱️ Calculer la durée réelle
        $session->ended_at = now();
        $durationMinutes = now()->diffInMinutes($session->start_time);

        // 💰 CALCUL DU PRIX selon le mode
        if ($pricingMode === 'per_match') {
            // Mode par match: prix par match × nombre de matchs
            $matchesPlayed = $request->matches_played;
            $pricePerMatch = $session->gamePricing->price;
            $session->matches_played = $matchesPlayed;
            $session->computed_price = $pricePerMatch * $matchesPlayed;
        } else {
            // Mode forfaitaire (temps): prix complet
            $session->computed_price = $session->gamePricing->price;
        }

        $session->status = 'completed';
        $session->save();

        // ✅ Libérer la machine
        $session->machine->update(['status' => 'available']);

        $response = [
            'message' => 'Session stopped successfully',
            'session' => $session->load(['machine', 'game', 'gamePricing.pricingMode']),
            'price' => $session->computed_price,
            'duration_used' => $durationMinutes . ' min',
            'payment_ready' => true
        ];

        // Informations spécifiques au mode
        if ($pricingMode === 'per_match') {
            $response['matches_played'] = $session->matches_played;
            $response['price_per_match'] = $session->gamePricing->price . ' DH';
            $response['calculation'] = $session->matches_played . ' match(s) × ' . $session->gamePricing->price . ' DH = ' . $session->computed_price . ' DH';
        } else {
            $response['duration_paid'] = $session->gamePricing->duration_minutes . ' min';
            $response['forfait'] = $session->gamePricing->duration_minutes . ' min = ' . $session->gamePricing->price . ' DH';
        }

        return response()->json($response);
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
            ->with('gamePricing.pricingMode', 'machine')
            ->get();

        $stoppedSessions = [];
        $debugInfo = [];

        foreach ($sessions as $session) {
            $pricingMode = $session->gamePricing->pricingMode->code;

            // Auto-stop seulement pour les sessions basées sur le temps
            if ($pricingMode === 'fixed' && $session->gamePricing->duration_minutes !== null) {
                $durationSeconds = $session->gamePricing->duration_minutes * 60;
                $elapsed = now()->diffInSeconds($session->start_time);

                // Debug info
                $debugInfo[] = [
                    'session_id' => $session->id,
                    'machine' => $session->machine->name,
                    'pricing_mode' => $pricingMode,
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
            } else {
                // Sessions par match ne s'arrêtent pas automatiquement
                $debugInfo[] = [
                    'session_id' => $session->id,
                    'machine' => $session->machine->name,
                    'pricing_mode' => $pricingMode,
                    'note' => 'Manual stop required (per-match pricing)'
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
        $session = GameSession::with(['gamePricing.pricingMode', 'machine'])->findOrFail($id);

        if ($session->ended_at) {
            return response()->json([
                'status' => 'completed',
                'message' => 'Session terminée'
            ]);
        }

        $pricingMode = $session->gamePricing->pricingMode->code;
        $elapsedSeconds = now()->diffInSeconds($session->start_time);
        $elapsedMinutes = floor($elapsedSeconds / 60);

        $response = [
            'status' => 'active',
            'pricing_mode' => $pricingMode,
            'elapsed_seconds' => $elapsedSeconds,
            'elapsed_minutes' => $elapsedMinutes,
            'machine' => $session->machine->name
        ];

        // Pour le mode par temps, calculer le temps restant
        if ($pricingMode === 'fixed' && $session->gamePricing->duration_minutes !== null) {
            $totalSeconds = $session->gamePricing->duration_minutes * 60;
            $remainingSeconds = max(0, $totalSeconds - $elapsedSeconds);
            $remainingMinutes = floor($remainingSeconds / 60);

            $response['remaining_seconds'] = $remainingSeconds;
            $response['remaining_minutes'] = $remainingMinutes;
            $response['will_auto_stop'] = $remainingSeconds <= 0;
            $response['forfait'] = $session->gamePricing->duration_minutes . ' min = ' . $session->gamePricing->price . ' DH';
        }

        // Pour le mode par match
        if ($pricingMode === 'per_match') {
            $response['price_per_match'] = $session->gamePricing->price . ' DH';
            $response['matches_count'] = $session->gamePricing->matches_count;
            $response['message'] = 'Saisir le nombre de matchs joués à la fin';
        }

        return response()->json($response);
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