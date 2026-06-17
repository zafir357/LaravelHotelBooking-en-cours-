<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@hotel.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Reception',
            'email' => 'reception@hotel.test',
            'password' => Hash::make('password'),
            'role' => 'receptionist',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Guest',
            'email' => 'guest@hotel.test',
            'password' => Hash::make('password'),
            'role' => 'guest',
            'email_verified_at' => now(),
        ]);
    }
}
