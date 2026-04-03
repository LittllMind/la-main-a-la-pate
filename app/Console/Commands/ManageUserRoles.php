<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ManageUserRoles extends Command
{
    /**
     * Nom et signature de la commande.
     *
     * @var string
     */
    protected $signature = 'user:role
                            {action : Action à effectuer (list, set, create)}
                            {--email= : Email de l\'utilisateur}
                            {--role= : Rôle à assigner (admin, employe, client)}
                            {--name= : Nom de l\'utilisateur (pour create)}
                            {--password= : Mot de passe (pour create)}';

    /**
     * Description de la commande.
     *
     * @var string
     */
    protected $description = 'Gérer les rôles des utilisateurs';

    /**
     * Exécute la commande.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->listUsers(),
            'set' => $this->setUserRole(),
            'create' => $this->createUser(),
            default => $this->error("Action inconnue: {$action}. Actions disponibles: list, set, create"),
        };
    }

    /**
     * Liste tous les utilisateurs avec leurs rôles.
     */
    private function listUsers(): int
    {
        $users = User::select('id', 'name', 'email', 'role', 'created_at')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $this->info('=== Utilisateurs et Rôles ===');
        $this->newLine();

        if ($users->isEmpty()) {
            $this->warn('Aucun utilisateur trouvé.');
            return 0;
        }

        $headers = ['ID', 'Nom', 'Email', 'Rôle', 'Créé le'];
        $rows = $users->map(fn($user) => [
            $user->id,
            $user->name,
            $user->email,
            $this->formatRole($user->role),
            $user->created_at->format('d/m/Y H:i'),
        ]);

        $this->table($headers, $rows);

        $this->newLine();
        $this->info("Total: {$users->count()} utilisateur(s)");

        return 0;
    }

    /**
     * Modifie le rôle d'un utilisateur.
     */
    private function setUserRole(): int
    {
        $email = $this->option('email');
        $role = $this->option('role');

        if (!$email) {
            $this->error('L\'option --email est requise pour l\'action "set".');
            return 1;
        }

        if (!$role) {
            $this->error('L\'option --role est requise pour l\'action "set".');
            return 1;
        }

        if (!in_array($role, ['admin', 'employe', 'client'])) {
            $this->error('Rôle invalide. Rôles disponibles: admin, employe, client');
            return 1;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Aucun utilisateur trouvé avec l'email: {$email}");
            return 1;
        }

        $oldRole = $user->role;
        $user->role = $role;
        $user->save();

        $this->info("✓ Rôle mis à jour pour {$user->name}: {$oldRole} → {$role}");
        return 0;
    }

    /**
     * Crée un nouvel utilisateur.
     */
    private function createUser(): int
    {
        $email = $this->option('email');
        $role = $this->option('role');
        $name = $this->option('name');
        $password = $this->option('password');

        if (!$email) {
            $this->error('L\'option --email est requise pour l\'action "create".');
            return 1;
        }

        if (!$name) {
            $this->error('L\'option --name est requise pour l\'action "create".');
            return 1;
        }

        if (!$password) {
            $this->error('L\'option --password est requise pour l\'action "create".');
            return 1;
        }

        if (!$role) {
            $role = 'client';
            $this->info("Rôle non spécifié, utilisation du rôle par défaut: {$role}");
        }

        if (!in_array($role, ['admin', 'employe', 'client'])) {
            $this->error('Rôle invalide. Rôles disponibles: admin, employe, client');
            return 1;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("Un utilisateur avec l'email {$email} existe déjà.");
            return 1;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
        ]);

        $this->info("✓ Utilisateur créé: {$name} ({$email}) avec le rôle {$role}");
        return 0;
    }

    /**
     * Formate le rôle pour l'affichage.
     */
    private function formatRole(string $role): string
    {
        return match ($role) {
            'admin' => '🔴 Admin',
            'employe' => '🟡 Employé',
            'client' => '🟢 Client',
            default => '⚪ ' . ucfirst($role),
        };
    }
}