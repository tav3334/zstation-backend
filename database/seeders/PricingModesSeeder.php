<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PricingMode;

class PricingModesSeeder extends Seeder
{
    public function run()
    {
        PricingMode::insert([
            ['code' => 'fixed', 'label' => 'Prix Fixe'],
        ]);
    }
}
