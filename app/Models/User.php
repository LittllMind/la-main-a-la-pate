<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'pseudonyme',
        'email',
        'password',
        'commune',
        'avatar',
        'rgpd_consent_at',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'rgpd_consent_at' => 'datetime',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCitoyen(): bool
    {
        return $this->role === 'citoyen' || $this->role === 'member' || $this->role === 'admin';
    }

    public function isInvite(): bool
    {
        return $this->role === 'invite';
    }

    public function displayName(): string
    {
        return $this->pseudonyme ?? $this->name;
    }

    public function avatarUrl(): string
    {
        return $this->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($this->displayName()).'&background=random';
    }
}
