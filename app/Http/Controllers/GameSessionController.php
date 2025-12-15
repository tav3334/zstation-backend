<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\GameSession;
use App\Models\Machine;
use App\Models\GamePricing;

class GameSessionController extends Controller
{
    public function startSession(Request $request)
{
    $data = $request->validate([
        'machine_id' => 'required|exists:machines,id',
        'game_id' => 'required|exists:games,id',
        'pricing_mode_id' => 'required|exists:pricing_modes,id',
        'pricing_reference_id' => 'required|exists:game_pricings,id',
        'customer_id' => 'nullable',
    ]);

    $session = \App\Models\GameSession::create([
        'machine_id' => $data['machine_id'],
        'game_id' => $data['game_id'],
        'pricing_mode_id' => $data['pricing_mode_id'],
        'pricing_reference_id' => $data['pricing_reference_id'],
        'customer_id' => $data['customer_id'],
        'start_time' => now(),
        'status' => 'active',
    ]);

    \App\Models\Machine::where('id', $data['machine_id'])
        ->update(['status' => 'in_session']);

    return response()->json([
        'message' => 'Session started',
        'session' => $session
    ]);
}


public function stopSession($id)
{
    // 1️⃣ جلب session
    $session = GameSession::find($id);

    if (!$session) {
        return response()->json([
            'error' => 'Session not found'
        ], 404);
    }

    // 2️⃣ سدّ session
    $session->end_time = now();
    $session->status = 'closed';

    // 3️⃣ حساب الثمن (safe)
    $pricing = GamePricing::find($session->pricing_reference_id);

    if ($pricing) {
        $session->computed_price = $pricing->price;
    } else {
        $session->computed_price = 0;
    }

    $session->save();

    // 4️⃣ رجّع machine available
    Machine::where('id', $session->machine_id)
        ->update(['status' => 'available']);

    return response()->json([
        'message' => 'Session stopped successfully',
        'price' => $session->computed_price
    ]);
}


}
