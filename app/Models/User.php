<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Relation : Un utilisateur a un panier
     */

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Relation : Un utilisateur a plusieurs commandes
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relation : Un utilisateur a plusieurs adresses
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Retourne l'adresse par défaut de l'utilisateur
     */
    public function defaultAddress(): ?Address
    {
        return $this->addresses()->where('is_default', true)->first();
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique.
     *
     * @param string|array $roles
     * @return bool
     */
    public function hasRole($roles): bool
    {
        $allowedRoles = is_array($roles) ? $roles : [$roles];
        return in_array($this->role, $allowedRoles);
    }

    /**
     * Vérifie si l'utilisateur est admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Vérifie si l'utilisateur est employé.
     *
     * @return bool
     */
    public function isEmploye(): bool
    {
        return $this->role === 'employe';
    }

    /**
     * Vérifie si l'utilisateur est employé ou admin.
     *
     * @return bool
     */
    public function isEmployeOrAdmin(): bool
    {
        return in_array($this->role, ['employe', 'admin']);
    }

    /**
     * Vérifie si l'utilisateur est client.
     *
     * @return bool
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Retourne le libellé du rôle.
     *
     * @return string
     */
    public function getRoleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Administrateur',
            'employe' => 'Employé',
            'client' => 'Client',
            default => ucfirst($this->role),
        };
    }

    /**
     * Retourne les rôles disponibles.
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
}
