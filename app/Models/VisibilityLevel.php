<?php

namespace App\Models;

enum VisibilityLevel: string
{
    case Working = 'working';
    case Citizen = 'citizen';
    case Public = 'public';

    /**
     * Détermine si un niveau de visibilité est lisible par l'utilisateur donné.
     * - public : tout le monde, y compris les guests
     * - citizen : membres authentifiés (citoyen ou supérieur)
     * - working : modérateurs et administrateurs seuls
     */
    public function visibleTo(?User $user): bool
    {
        return match ($this) {
            self::Public => true,
            self::Citizen => $user !== null,
            self::Working => $user !== null && $user->isModeratorOrAdmin(),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Working => 'Travail',
            self::Citizen => 'Citoyen',
            self::Public => 'Public',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Public => 'Visible publiquement',
            self::Citizen => 'Visible des membres connectés',
            self::Working => "Visible de l'équipe uniquement",
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Public => 'emerald',
            self::Citizen => 'blue',
            self::Working => 'slate',
        };
    }
}
