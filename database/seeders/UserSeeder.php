<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $email    = config('lmalp.admin_email');
        $password = config('lmalp.admin_password');

        if (empty($email) || empty($password)) {
            throw new \RuntimeException(
                'LMALP_ADMIN_EMAIL and LMALP_ADMIN_PASSWORD must be set in .env'
            );
        }

        User::create([
            'username'         => 'aurelien',
            'name'             => 'Aurélien',
            'pseudonyme'       => 'littllmind',
            'email'            => $email,
            'email_verified_at' => now(),
            'password'         => $password,
            'requires_setup'   => false,
            'role'             => 'admin',
            'rgpd_consent_at'  => now(),
        ]);
    }
}
