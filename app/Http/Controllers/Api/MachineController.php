<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machine;

class MachineController extends Controller
{
    public function index()
    {
        $machines = Machine::with([
            'activeSession.gamePricing',
            'activeSession.game'
        ])->get();

        // Transformer les données pour le frontend
        $result = $machines->map(function ($machine) {
            // Extraire les données de base de la machine
            $data = [
                'id' => $machine->id,
                'name' => $machine->name,
                'status' => $machine->status,
                'created_at' => $machine->created_at,
                'updated_at' => $machine->updated_at,
                'active_session' => null
            ];

            // Récupérer la session active (objet Eloquent, pas tableau)
            $session = $machine->getRelation('activeSession');

            // Si la machine a une session active, formater les données
            if ($session) {
                // Vérifier que gamePricing est chargé
                if (!$session->gamePricing) {
                    \Log::error("Session {$session->id} has no gamePricing! pricing_reference_id: {$session->pricing_reference_id}");
                    return $data; // Retourner sans active_session
                }

                $durationMinutes = $session->gamePricing->duration_minutes ?? 6;

                $data['active_session'] = [
                    'id' => $session->id,
                    'start_time' => $session->start_time->toISOString(),
                    'game_name' => $session->game->name ?? 'N/A',
                    'price' => $session->computed_price,
                    'duration_minutes' => $durationMinutes,
                    'duration_seconds' => $durationMinutes * 60
                ];
            }

            return $data;
        });

        return response()->json($result);
    }
}
