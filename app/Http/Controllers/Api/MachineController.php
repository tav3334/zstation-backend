<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machine;

class MachineController extends Controller
{
    public function index()
    {
        // Utiliser directement le modèle Machine avec son attribut virtuel active_session
        // qui gère correctement pricing_mode et matches_count
        $machines = Machine::all();

        return response()->json($machines);
    }
}
