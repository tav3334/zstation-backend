<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Machine;

class MachinesSeeder extends Seeder
{
    public function run()
    {
        for ($i = 1; $i <= 5; $i++) {
            Machine::create([
                'name' => "PS5 #$i",
                'status' => 'available',
            ]);
        }
    }
}
