<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'nom',
        'email',
        'telephone',
        'adresse',
        'code_postal',
        'ville',
        'pays',
        'instructions',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute(): string
    {
        return "{$this->adresse}, {$this->code_postal} {$this->ville}, {$this->pays}";
    }

    public function setAsDefault(): void
    {
        // Désactiver toutes les autres adresses de l'utilisateur
        static::where('user_id', $this->user_id)->update(['is_default' => false]);
        
        // Activer celle-ci
        $this->update(['is_default' => true]);
    }
}
