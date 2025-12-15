<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\GameType;

class GamesSeeder extends Seeder
{
    public function run()
    {
        $sport = GameType::where('name', 'Sport')->first();
        $libre = GameType::where('name', 'Jeu Libre')->first();

        Game::insert([
            ['name' => 'FIFA / PES', 'game_type_id' => $sport->id, 'active' => 1],
            ['name' => 'GTA 5', 'game_type_id' => $libre->id, 'active' => 1],
            ['name' => 'Call of Duty', 'game_type_id' => $libre->id, 'active' => 1],
            ['name' => 'Battlefield', 'game_type_id' => $libre->id, 'active' => 1],
            ['name' => 'F1', 'game_type_id' => $libre->id, 'active' => 1],
        ]);
    }
}
