<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameSession;
use App\Models\Machine;
use Illuminate\Support\Facades\DB;

class FixSessionsController extends Controller
{
    /**
     * Arrêter toutes les sessions actives et réinitialiser les machines
     * ENDPOINT TEMPORAIRE - SUPPRIMER APRÈS UTILISATION
     */
    public function stopAllSessions()
    {
        try {
            $results = [];

            // 1. Compter les sessions actives
            $activeSessions = GameSession::whereNull('ended_at')->count();
            $results[] = "Sessions actives trouvées: $activeSessions";

            // 2. Arrêter toutes les sessions actives
            $updated = GameSession::whereNull('ended_at')
                ->update([
                    'ended_at' => now(),
                    'status' => 'completed'
                ]);

            $results[] = "✓ Sessions arrêtées: $updated";

            // 3. Réinitialiser le statut de toutes les machines
            $machinesUpdated = Machine::where('status', 'in_session')
                ->update(['status' => 'available']);

            $results[] = "✓ Machines réinitialisées: $machinesUpdated";

            // 4. Vérifier qu'il ne reste plus de sessions actives
            $remaining = GameSession::whereNull('ended_at')->count();
            $results[] = "Sessions actives restantes: $remaining";

            return response()->json([
                'success' => true,
                'message' => 'Toutes les sessions ont été arrêtées',
                'details' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
