<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'username' => 'admin',
                'password_hash' => Hash::make('admin123'),
                'full_name' => 'System Administrator',
                'email' => 'admin@somali-pos.com',
                'phone' => '000000000',
                'role' => 'admin',
                'is_enabled' => true,
                'is_online' => false,
                'plain_password' => 'admin123',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['username' => $userData['username']],
                $userData
            );
        }
    }
}
