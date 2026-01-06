<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'admin@bisnis.test'],
            [
                'name' => 'Admin BisnisPanel',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'owner@bisnis.test'],
            [
                'name' => 'Business Owner',
                'password' => Hash::make('password'),
                'role' => User::ROLE_USER,
                'email_verified_at' => now(),
            ]
        );
    }
}
