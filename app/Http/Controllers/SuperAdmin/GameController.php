<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GamePricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
    /**
     * Display a listing of all games with pricing (both time-based and match-based)
     */
    public function index()
    {
        try {
            $games = Game::with(['pricings.pricingMode'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($game) {
                    return [
                        'id' => $game->id,
                        'name' => $game->name,
                        'active' => $game->active,
                        'pricings' => $game->pricings->map(function ($pricing) {
                            return [
                                'id' => $pricing->id,
                                'pricing_mode' => [
                                    'id' => $pricing->pricingMode->id ?? null,
                                    'code' => $pricing->pricingMode->code ?? 'fixed',
                                    'label' => $pricing->pricingMode->label ?? 'Prix Fixe',
                                ],
                                'duration_minutes' => $pricing->duration_minutes,
                                'matches_count' => $pricing->matches_count,
                                'price' => $pricing->price,
                            ];
                        }),
                        'created_at' => $game->created_at,
                        'updated_at' => $game->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'games' => $games
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des jeux',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created game with pricing
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price_6min' => 'required|numeric|min:0',
            'price_30min' => 'required|numeric|min:0',
            'price_1h' => 'required|numeric|min:0',
            'price_2h' => 'required|numeric|min:0',
            'price_3h' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get or create default game type
            $gameType = \App\Models\GameType::first();
            if (!$gameType) {
                $gameType = \App\Models\GameType::create(['name' => 'Standard']);
            }

            // Get default pricing mode
            $pricingMode = \App\Models\PricingMode::where('code', 'fixed')->first();
            if (!$pricingMode) {
                $pricingMode = \App\Models\PricingMode::first();
            }
            $pricingModeId = $pricingMode ? $pricingMode->id : 1;

            // Create game
            $game = Game::create([
                'name' => $request->name,
                'active' => true,
                'game_type_id' => $gameType->id
            ]);

            // Create pricing entries
            $pricings = [
                ['duration_minutes' => 6, 'price' => $request->price_6min],
                ['duration_minutes' => 30, 'price' => $request->price_30min],
                ['duration_minutes' => 60, 'price' => $request->price_1h],
                ['duration_minutes' => 120, 'price' => $request->price_2h],
                ['duration_minutes' => 180, 'price' => $request->price_3h],
            ];

            foreach ($pricings as $pricing) {
                GamePricing::create([
                    'game_id' => $game->id,
                    'pricing_mode_id' => $pricingModeId,
                    'duration_minutes' => $pricing['duration_minutes'],
                    'price' => $pricing['price']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jeu créé avec succès',
                'game' => [
                    'id' => $game->id,
                    'name' => $game->name,
                    'price_6min' => $request->price_6min,
                    'price_30min' => $request->price_30min,
                    'price_1h' => $request->price_1h,
                    'price_2h' => $request->price_2h,
                    'price_3h' => $request->price_3h,
                    'created_at' => $game->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du jeu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified game
     */
    public function show($id)
    {
        try {
            $game = Game::with('pricings')->findOrFail($id);

            $pricing6min = $game->pricings->firstWhere('duration_minutes', 6);
            $pricing30min = $game->pricings->firstWhere('duration_minutes', 30);
            $pricing1h = $game->pricings->firstWhere('duration_minutes', 60);
            $pricing2h = $game->pricings->firstWhere('duration_minutes', 120);
            $pricing3h = $game->pricings->firstWhere('duration_minutes', 180);

            return response()->json([
                'success' => true,
                'game' => [
                    'id' => $game->id,
                    'name' => $game->name,
                    'price_6min' => $pricing6min ? $pricing6min->price : 0,
                    'price_30min' => $pricing30min ? $pricing30min->price : 0,
                    'price_1h' => $pricing1h ? $pricing1h->price : 0,
                    'price_2h' => $pricing2h ? $pricing2h->price : 0,
                    'price_3h' => $pricing3h ? $pricing3h->price : 0,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Jeu non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified game
     */
    public function update(Request $request, $id)
    {
        try {
            $game = Game::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'price_6min' => 'sometimes|required|numeric|min:0',
                'price_30min' => 'sometimes|required|numeric|min:0',
                'price_1h' => 'sometimes|required|numeric|min:0',
                'price_2h' => 'sometimes|required|numeric|min:0',
                'price_3h' => 'sometimes|required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Update game name
            if ($request->has('name')) {
                $game->name = $request->name;
                $game->save();
            }

            // Update pricing entries
            if ($request->has('price_6min')) {
                $this->updateOrCreatePricing($game->id, 6, $request->price_6min);
            }
            if ($request->has('price_30min')) {
                $this->updateOrCreatePricing($game->id, 30, $request->price_30min);
            }
            if ($request->has('price_1h')) {
                $this->updateOrCreatePricing($game->id, 60, $request->price_1h);
            }
            if ($request->has('price_2h')) {
                $this->updateOrCreatePricing($game->id, 120, $request->price_2h);
            }
            if ($request->has('price_3h')) {
                $this->updateOrCreatePricing($game->id, 180, $request->price_3h);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jeu modifié avec succès',
                'game' => [
                    'id' => $game->id,
                    'name' => $game->name,
                    'updated_at' => $game->updated_at,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du jeu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified game
     * Super Admin can delete games with all associated sessions and pricings
     */
    public function destroy($id)
    {
        try {
            $game = Game::findOrFail($id);

            DB::beginTransaction();

            // Delete all associated sessions first (they reference pricings)
            \App\Models\GameSession::where('game_id', $id)->delete();

            // Delete associated pricings
            GamePricing::where('game_id', $id)->delete();

            // Delete game
            $game->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jeu supprimé avec succès (avec toutes les sessions et tarifs associés)'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du jeu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function to update or create pricing
     */
    private function updateOrCreatePricing($gameId, $durationMinutes, $price)
    {
        // Get default pricing mode
        $pricingMode = \App\Models\PricingMode::where('code', 'fixed')->first();
        $pricingModeId = $pricingMode ? $pricingMode->id : 1;

        GamePricing::updateOrCreate(
            [
                'game_id' => $gameId,
                'duration_minutes' => $durationMinutes
            ],
            [
                'price' => $price,
                'pricing_mode_id' => $pricingModeId
            ]
        );
    }

    /**
     * Add a new pricing to a game (time-based or match-based)
     */
    public function addPricing(Request $request, $gameId)
    {
        $validator = Validator::make($request->all(), [
            'pricing_mode_id' => 'required|integer|exists:pricing_modes,id',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'matches_count' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate that at least one of duration_minutes or matches_count is provided
        if (!$request->duration_minutes && !$request->matches_count) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez spécifier soit la durée en minutes, soit le nombre de matchs'
            ], 422);
        }

        try {
            $game = Game::findOrFail($gameId);

            $pricing = GamePricing::create([
                'game_id' => $gameId,
                'pricing_mode_id' => $request->pricing_mode_id,
                'duration_minutes' => $request->duration_minutes,
                'matches_count' => $request->matches_count,
                'price' => $request->price,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tarif ajouté avec succès',
                'pricing' => $pricing
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du tarif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a specific pricing
     */
    public function updatePricing(Request $request, $gameId, $pricingId)
    {
        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'matches_count' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $pricing = GamePricing::where('game_id', $gameId)
                ->where('id', $pricingId)
                ->firstOrFail();

            $pricing->update([
                'price' => $request->price,
                'duration_minutes' => $request->duration_minutes,
                'matches_count' => $request->matches_count,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tarif modifié avec succès',
                'pricing' => $pricing
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du tarif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific pricing
     */
    public function deletePricing($gameId, $pricingId)
    {
        try {
            $pricing = GamePricing::where('game_id', $gameId)
                ->where('id', $pricingId)
                ->firstOrFail();

            DB::beginTransaction();

            // Delete sessions that reference this pricing (since the column is not nullable)
            \App\Models\GameSession::where('pricing_reference_id', $pricingId)->delete();

            $pricing->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tarif supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du tarif',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
