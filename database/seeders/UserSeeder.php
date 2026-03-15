<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('pass'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Employé',
            'email' => 'employe@test.com',
            'password' => Hash::make('pass'),
            'role' => 'employe',
        ]);

        User::create([
            'name' => 'Client',
            'email' => 'client@test.com',
            'password' => Hash::make('pass'),
            'role' => 'client',
        ]);
    }
}
