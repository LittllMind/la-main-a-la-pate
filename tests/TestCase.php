<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;
    
    /**
     * Indique que la base doit être réinitialisée avant chaque test
     */
    protected bool $seed = false;

    /**
     * Crée un utilisateur admin
     */
    protected function adminUser(): \App\Models\User
    {
        return \App\Models\User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
        ]);
    }

    /**
     * Crée un utilisateur employé
     */
    protected function employeUser(): \App\Models\User
    {
        return \App\Models\User::factory()->create([
            'role' => 'employe',
            'email' => 'employe@example.com',
        ]);
    }

    /**
     * Crée un utilisateur client
     */
    protected function clientUser(): \App\Models\User
    {
        return \App\Models\User::factory()->create([
            'role' => 'client',
            'email' => 'client@example.com',
        ]);
    }

    /**
     * Se connecte en tant qu'utilisateur
     */
    protected function actingAsUser(string $role = 'client'): static
    {
        $user = match ($role) {
            'admin' => $this->adminUser(),
            'employe' => $this->employeUser(),
            default => $this->clientUser(),
        };

        return $this->actingAs($user);
    }
}
