<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GamePricing;

class GameController extends Controller
{
    /**
     * Get games filtered by organization (via Global Scope)
     */
    public function index()
    {
        try {
            // Le Global Scope 'organization' filtre automatiquement par organisation
            // pour les utilisateurs authentifiés (sauf SuperAdmin)
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

    /**
     * Get game pricings filtered by organization
     */
    public function pricings()
    {
        try {
            $user = auth()->user();

            // Get game IDs for the user's organization (using Global Scope)
            $gameIds = Game::where('active', true)->pluck('id');

            $pricings = GamePricing::whereIn('game_id', $gameIds)
                ->with(['pricingMode:id,code,label', 'game:id,name'])
                ->orderBy('game_id', 'asc')
                ->orderBy('pricing_mode_id', 'asc')
                ->orderBy('duration_minutes', 'asc')
                ->orderBy('matches_count', 'asc')
                ->get();

            return response()->json($pricings);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}