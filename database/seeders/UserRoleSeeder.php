<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserRoleSeeder extends Seeder
{
    /**
     * Rôles forum-municipal :
     *   admin     → "Le Conseil Municipal" (gestion, modération)
     *   moderator → "Modérateur" (édition wiki, publication)
     *   citoyen   → "Citoyen" (membre inscrit, peut participer)
     *   invite    → "Visiteur" (accès lecture limitée)
     */
    public function run(): void
    {
        $users = [
            [
                'name'       => 'Le Conseil Municipal',
                'pseudonyme' => 'LaMairie',
                'email'      => 'admin@lamainalapate.test',
                'password'   => 'password',
                'commune'    => 'Commune Test',
                'role'       => 'admin',
            ],
            [
                'name'       => 'Pierre Modérateur',
                'pseudonyme' => 'PierreModo',
                'email'      => 'moderator@lamainalapate.test',
                'password'   => 'password',
                'commune'    => 'Commune Test',
                'role'       => 'moderator',
            ],
            [
                'name'       => 'Marie Dupont',
                'pseudonyme' => 'MarieDuVillage',
                'email'      => 'citoyen@lamainalapate.test',
                'password'   => 'password',
                'commune'    => 'Commune Test',
                'role'       => 'citoyen',
            ],
            [
                'name'       => 'Visiteur Occasionnel',
                'pseudonyme' => 'LePassant',
                'email'      => 'visiteur@lamainalapate.test',
                'password'   => 'password',
                'commune'    => null,
                'role'       => 'invite',
            ],
        ];

        foreach ($users as $data) {
            $data['email_verified_at'] = now();
            User::updateOrCreate(
                ['email' => $data['email']],
                $data
            );
        }
    }
}
