<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Re-creates demo accounts with known credentials.
     * Run: php artisan db:seed
     */
    public function run()
    {
        // Demo admin account
        User::updateOrCreate(
            ['email' => 'admin@hostarea.dev'],
            [
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'role'     => 'admin',
            ]
        );

        // Demo regular user account  (matches the test account shown in screenshots)
        User::updateOrCreate(
            ['email' => 'zw123456f@gmail.com'],
            [
                'username' => 'zw123456f',
                'password' => Hash::make('password123'),
                'role'     => 'user',
            ]
        );
    }
}
