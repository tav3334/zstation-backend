<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            GameTypesSeeder::class,
            PricingModesSeeder::class,
            GamesSeeder::class,
            GamePricingsSeeder::class,
            MachinesSeeder::class,
        ]);
    }
}
