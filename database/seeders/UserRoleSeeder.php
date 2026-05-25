<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    /**
     * Rôles forum-municipal :
     *   admin   → "Le Conseil Municipal" (gestion, modération)
     *   citoyen → "Citoyen" (membre inscrit, peut participer)
     *   invite  → "Visiteur" (accès lecture limitée)
     */
    public function run(): void
    {
        $password = Hash::make('password');

        $users = [
            [
                'name'       => 'Le Conseil Municipal',
                'pseudonyme' => 'LaMairie',
                'email'      => 'admin@lamainalapate.test',
                'password'   => $password,
                'commune'    => 'Commune Test',
                'role'       => 'admin',
            ],
            [
                'name'       => 'Marie Dupont',
                'pseudonyme' => 'MarieDuVillage',
                'email'      => 'citoyen@lamainalapate.test',
                'password'   => $password,
                'commune'    => 'Commune Test',
                'role'       => 'citoyen',
            ],
            [
                'name'       => 'Visiteur Occasionnel',
                'pseudonyme' => 'LePassant',
                'email'      => 'visiteur@lamainalapate.test',
                'password'   => $password,
                'commune'    => null,
                'role'       => 'invite',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                $data
            );
        }
    }
}
