<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ProductionUserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin principal (à configurer avec vos vraies données)
        User::create([
            'name' => 'Administrateur Principal',
            'email' => 'admin@la-main-a-la-pate.online',
            'email_verified_at' => now(),
            'password' => Hash::make('CHANGE_ME_SECURE_PASSWORD'), // À changer IMMÉDIATEMENT
        ]);

        // Compte kiosque (optionnel, si besoin d'un compte de secours)
        User::create([
            'name' => 'Compte Kiosque',
            'email' => 'kiosque@la-main-a-la-pate.online',
            'email_verified_at' => now(),
            'password' => Hash::make('CHANGE_ME_KIOSQUE_PASSWORD'),
        ]);
    }
}
