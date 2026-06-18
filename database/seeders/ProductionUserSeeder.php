<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ProductionUserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin principal. Le mot de passe est fourni manuellement et change immediatement apres le seed.
        User::create([
            'name' => 'Administrateur Principal',
            'email' => 'admin@la-main-a-la-pate.online',
            'email_verified_at' => now(),
            'password' => Hash::make(env('SEEDER_ADMIN_PASSWORD') ?: throw new \Exception('SEEDER_ADMIN_PASSWORD non défini dans .env prod')),
            'role' => 'admin',
        ]);
    }
}
