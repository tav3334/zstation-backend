<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\PricingMode;
use App\Models\GamePricing;

class GamePricingsSeeder extends Seeder
{
    public function run()
    {
        $fixed = PricingMode::where('code', 'fixed')->first();

        $fifa = Game::where('name', 'FIFA / PES')->first();

        GamePricing::create([
            'game_id' => $fifa->id,
            'pricing_mode_id' => $fixed->id,
            'duration_minutes' => 6,
            'price' => 6,
        ]);

        $libreGames = Game::where('name', '!=', 'FIFA / PES')->get();

        foreach ($libreGames as $game) {
            GamePricing::insert([
                [
                    'game_id' => $game->id,
                    'pricing_mode_id' => $fixed->id,
                    'duration_minutes' => 30,
                    'price' => 10,
                ],
                [
                    'game_id' => $game->id,
                    'pricing_mode_id' => $fixed->id,
                    'duration_minutes' => 60,
                    'price' => 20,
                ],
            ]);
        }
    }
}
