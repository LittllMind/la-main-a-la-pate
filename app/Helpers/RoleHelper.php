<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class RoleHelper
{
    /**
     * Vérifie si l'utilisateur authentifié a un rôle spécifique.
     *
     * @param string|array $roles
     * @return bool
     */
    public static function hasRole($roles): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $userRole = Auth::user()->role;
        $allowedRoles = is_array($roles) ? $roles : [$roles];

        return in_array($userRole, $allowedRoles);
    }

    /**
     * Vérifie si l'utilisateur est admin.
     *
     * @return bool
     */
    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }

    /**
     * Vérifie si l'utilisateur est employé ou admin.
     *
     * @return bool
     */
    public static function isEmployeOrAdmin(): bool
    {
        return self::hasRole(['employe', 'admin']);
    }

    /**
     * Vérifie si l'utilisateur est client.
     *
     * @return bool
     */
    public static function isClient(): bool
    {
        return self::hasRole('client');
    }

    /**
     * Retourne le rôle de l'utilisateur authentifié.
     *
     * @return string|null
     */
    public static function getCurrentRole(): ?string
    {
        return Auth::check() ? Auth::user()->role : null;
    }

    /**
     * Liste des rôles disponibles.
     *
     * @return array
     */
    public static function getAvailableRoles(): array
    {
        return [
            'admin' => 'Administrateur',
            'employe' => 'Employé',
            'client' => 'Client',
        ];
    }

    /**
     * Vérifie si un rôle est valide.
     *
     * @param string $role
     * @return bool
     */
    public static function isValidRole(string $role): bool
    {
        return array_key_exists($role, self::getAvailableRoles());
    }
}