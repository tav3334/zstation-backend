<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 👑 ADMIN (Patron)
        User::firstOrCreate(
            ['email' => 'admin@zstation.ma'],
            [
                'name' => 'Admin ZSTATION',
                'password' => Hash::make('admin123'),
                'role' => 'admin'
            ]
        );

        // 👤 AGENT (Caissier)
        User::firstOrCreate(
            ['email' => 'agent@zstation.ma'],
            [
                'name' => 'Agent 1',
                'password' => Hash::make('agent123'),
                'role' => 'agent'
            ]
        );

        $this->command->info('✅ Utilisateurs créés:');
        $this->command->info('👑 Admin: admin@zstation.ma / admin123');
        $this->command->info('👤 Agent: agent@zstation.ma / agent123');
    }
}