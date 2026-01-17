<?php

namespace App\Http\Controllers;

use App\Models\Game;

class GameController extends Controller
{
    public function index()
    {
        try {
            $games = Game::select('id', 'name', 'active')
                ->with(['pricings' => function($query) {
                    $query->select('id', 'game_id', 'pricing_mode_id', 'duration_minutes', 'matches_count', 'price')
                        ->with('pricingMode:id,code,label')
                        ->orderBy('pricing_mode_id', 'asc')
                        ->orderBy('duration_minutes', 'asc')
                        ->orderBy('matches_count', 'asc');
                }])
                ->where('active', true)
                ->get();

            return response()->json($games);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}