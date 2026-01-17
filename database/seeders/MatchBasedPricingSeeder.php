<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\GamePricing;
use App\Models\PricingMode;

class MatchBasedPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtenir le pricing_mode "per_match"
        $perMatchMode = PricingMode::where('code', 'per_match')->first();

        if (!$perMatchMode) {
            $this->command->error('Le mode de tarification "per_match" n\'existe pas. Exécutez d\'abord la migration.');
            return;
        }

        // Trouver FIFA et PES
        $fifaGames = Game::whereIn('name', ['FIFA 24', 'FC 24'])->get();
        $pesGames = Game::where('name', 'PES 2024')->get();
        $sportGames = $fifaGames->merge($pesGames);

        if ($sportGames->isEmpty()) {
            $this->command->error('Aucun jeu FIFA ou PES trouvé.');
            return;
        }

        // Ajouter le tarif "par match" pour chaque jeu sport
        foreach ($sportGames as $game) {
            // Vérifier si le tarif par match existe déjà
            $existingPricing = GamePricing::where('game_id', $game->id)
                ->where('pricing_mode_id', $perMatchMode->id)
                ->where('matches_count', 1)
                ->first();

            if (!$existingPricing) {
                GamePricing::create([
                    'game_id' => $game->id,
                    'pricing_mode_id' => $perMatchMode->id,
                    'duration_minutes' => null,
                    'matches_count' => 1,
                    'price' => 6.00
                ]);

                $this->command->info("Tarif par match ajouté pour {$game->name}: 1 match = 6 DH");
            } else {
                $this->command->info("Tarif par match existe déjà pour {$game->name}");
            }
        }

        $this->command->info('Tarification par match configurée avec succès pour FIFA et PES!');
    }
}
