<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'username' => 'aurelien',
            'name' => 'Aurélien',
            'pseudonyme' => 'littllmind',
            'email' => 'aurelien.tisserand18@gmail.com',
            'email_verified_at' => now(),
            'password' => \Illuminate\Support\Facades\Hash::make('pass'),
            'requires_setup' => false,
            'role' => 'admin',
            'rgpd_consent_at' => now(),
        ]);
    }
}
