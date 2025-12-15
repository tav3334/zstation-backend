<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GameType;

class GameTypesSeeder extends Seeder
{
    public function run()
    {
        GameType::insert([
            ['name' => 'Sport'],
            ['name' => 'Jeu Libre'],
        ]);
    }
}
