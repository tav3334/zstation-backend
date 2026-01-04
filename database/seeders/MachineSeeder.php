<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MachineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $machines = [
            ['name' => 'PS5 - Station 1', 'status' => 'available'],
            ['name' => 'PS5 - Station 2', 'status' => 'available'],
            ['name' => 'PS5 - Station 3', 'status' => 'available'],
            ['name' => 'PS5 - Station 4', 'status' => 'available'],
            ['name' => 'PS5 - VIP 1', 'status' => 'available'],
            ['name' => 'PS5 - VIP 2', 'status' => 'available'],
        ];

        foreach ($machines as $machine) {
            \App\Models\Machine::updateOrCreate(
                ['name' => $machine['name']],
                $machine
            );
        }

        $this->command->info('✅ 6 machines créées avec succès!');
    }
}
