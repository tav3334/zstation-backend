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
     * Display a listing of all games with pricing
     */
    public function index()
    {
        try {
            $games = Game::with('pricings')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($game) {
                    // Find pricings for standard durations
                    $pricing6min = $game->pricings->firstWhere('duration_minutes', 6);
                    $pricing30min = $game->pricings->firstWhere('duration_minutes', 30);
                    $pricing1h = $game->pricings->firstWhere('duration_minutes', 60);
                    $pricing2h = $game->pricings->firstWhere('duration_minutes', 120);
                    $pricing3h = $game->pricings->firstWhere('duration_minutes', 180);

                    return [
                        'id' => $game->id,
                        'name' => $game->name,
                        'price_6min' => $pricing6min ? $pricing6min->price : 0,
                        'price_30min' => $pricing30min ? $pricing30min->price : 0,
                        'price_1h' => $pricing1h ? $pricing1h->price : 0,
                        'price_2h' => $pricing2h ? $pricing2h->price : 0,
                        'price_3h' => $pricing3h ? $pricing3h->price : 0,
                        'active' => $game->active,
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

            // Create game
            $game = Game::create([
                'name' => $request->name,
                'active' => true,
                'game_type_id' => 1, // Default game type
                'default_price_per_hour' => $request->price_1h
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
                    'pricing_mode_id' => 1, // Default pricing mode
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
     */
    public function destroy($id)
    {
        try {
            $game = Game::findOrFail($id);

            DB::beginTransaction();

            // Delete associated pricings
            GamePricing::where('game_id', $id)->delete();

            // Delete game
            $game->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jeu supprimé avec succès'
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
        GamePricing::updateOrCreate(
            [
                'game_id' => $gameId,
                'duration_minutes' => $durationMinutes
            ],
            [
                'price' => $price,
                'pricing_mode_id' => 1
            ]
        );
    }
}
