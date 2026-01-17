<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GamePricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les données de base d'abord
        \DB::table('pricing_modes')->updateOrInsert(
            ['id' => 1],
            ['code' => 'fixed', 'label' => 'Prix Fixe', 'created_at' => now(), 'updated_at' => now()]
        );
        \DB::table('game_types')->insertOrIgnore(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        // Créer les jeux populaires
        $games = [
            ['name' => 'FIFA 24', 'active' => true, 'game_type_id' => 1],
            ['name' => 'Call of Duty', 'active' => true, 'game_type_id' => 1],
            ['name' => 'Fortnite', 'active' => true, 'game_type_id' => 1],
            ['name' => 'GTA V', 'active' => true, 'game_type_id' => 1],
            ['name' => 'Spider-Man', 'active' => true, 'game_type_id' => 1],
            ['name' => 'God of War', 'active' => true, 'game_type_id' => 1],
        ];

        foreach ($games as $gameData) {
            $game = \App\Models\Game::updateOrCreate(
                ['name' => $gameData['name']],
                $gameData
            );

            // Créer les tarifs pour chaque jeu
            $pricings = [
                ['duration_minutes' => 6, 'price' => 6.00],
                ['duration_minutes' => 30, 'price' => 10.00],
                ['duration_minutes' => 60, 'price' => 20.00],
            ];

            foreach ($pricings as $pricing) {
                \App\Models\GamePricing::updateOrCreate(
                    [
                        'game_id' => $game->id,
                        'duration_minutes' => $pricing['duration_minutes']
                    ],
                    [
                        'game_id' => $game->id,
                        'duration_minutes' => $pricing['duration_minutes'],
                        'price' => $pricing['price'],
                        'pricing_mode_id' => 1
                    ]
                );
            }
        }

        $this->command->info('✅ 6 jeux et 18 tarifs créés avec succès!');
    }
}
