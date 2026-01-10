<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@zstation.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin'
            ]
        );

        // Create agent user
        User::firstOrCreate(
            ['email' => 'agent@zstation.com'],
            [
                'name' => 'Agent',
                'password' => Hash::make('password'),
                'role' => 'agent'
            ]
        );

        echo "✅ Test users created:\n";
        echo "   Admin: admin@zstation.com / password\n";
        echo "   Agent: agent@zstation.com / password\n";
    }
}
