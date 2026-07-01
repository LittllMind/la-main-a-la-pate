<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Aurélien',
            'pseudonyme' => 'littllmind',
            'email' => 'aurelien.tisserand18@gmail.com',
            'email_verified_at' => now(),
            'password' => 'NewProduction18@L',
            'role' => 'admin',
            'rgpd_consent_at' => now(),
            'email_verified_at' => now(),
        ]);
    }
}
