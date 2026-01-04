<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GameType;
use App\Models\Game;
use App\Models\PricingMode;
use App\Models\GamePricing;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        // 1️⃣ Créer les types de jeux
        $sportType = GameType::firstOrCreate(['name' => 'Sport']);
        $freePlayType = GameType::firstOrCreate(['name' => 'Jeu Libre']);

        // 2️⃣ Créer les jeux SPORT (FIFA/PES)
        $fifa = Game::firstOrCreate(
            ['name' => 'FIFA 24'],
            ['game_type_id' => $sportType->id, 'active' => true]
        );

        $pes = Game::firstOrCreate(
            ['name' => 'PES 2024'],
            ['game_type_id' => $sportType->id, 'active' => true]
        );

        // 3️⃣ Créer des jeux libres populaires
        $cod = Game::firstOrCreate(
            ['name' => 'Call of Duty'],
            ['game_type_id' => $freePlayType->id, 'active' => true]
        );

        $fortnite = Game::firstOrCreate(
            ['name' => 'Fortnite'],
            ['game_type_id' => $freePlayType->id, 'active' => true]
        );

        $spiderman = Game::firstOrCreate(
            ['name' => 'Spider-Man 2'],
            ['game_type_id' => $freePlayType->id, 'active' => true]
        );

        $gta = Game::firstOrCreate(
            ['name' => 'GTA V'],
            ['game_type_id' => $freePlayType->id, 'active' => true]
        );

        $fc24 = Game::firstOrCreate(
            ['name' => 'FC 24'],
            ['game_type_id' => $sportType->id, 'active' => true]
        );

        // 4️⃣ Créer le mode de tarification
        $fixedMode = PricingMode::firstOrCreate(
            ['code' => 'fixed'],
            ['label' => 'Prix Fixe']
        );

        // 5️⃣ TARIFS FIFA/PES (6 min = 6 DH uniquement)
        $sportGames = [$fifa, $pes, $fc24];
        
        foreach ($sportGames as $game) {
            GamePricing::firstOrCreate([
                'game_id' => $game->id,
                'pricing_mode_id' => $fixedMode->id,
                'duration_minutes' => 6,
                'price' => 6.00
            ]);
        }

        // 6️⃣ TARIFS JEU LIBRE (30 min = 10 DH, 1h = 20 DH)
        $freePlayGames = [$cod, $fortnite, $spiderman, $gta];
        
        foreach ($freePlayGames as $game) {
            // 30 minutes - 10 DH
            GamePricing::firstOrCreate([
                'game_id' => $game->id,
                'pricing_mode_id' => $fixedMode->id,
                'duration_minutes' => 30,
                'price' => 10.00
            ]);

            // 60 minutes - 20 DH
            GamePricing::firstOrCreate([
                'game_id' => $game->id,
                'pricing_mode_id' => $fixedMode->id,
                'duration_minutes' => 60,
                'price' => 20.00
            ]);
        }

        $this->command->info('✅ Tarifs ZSTATION créés avec succès!');
        $this->command->info('📋 FIFA/PES: 6 min = 6 DH');
        $this->command->info('📋 Jeu libre: 30 min = 10 DH, 1h = 20 DH');
    }
}