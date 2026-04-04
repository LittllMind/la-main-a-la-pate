<?php
// app/Models/Cart.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Relation : Un panier appartient à un utilisateur (optionnel)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Un panier contient plusieurs items
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Taux de TVA (20% pour la France)
     */
    protected const TVA_RATE = 0.20;

    /**
     * Calcul du total HT du panier (Accessor)
     */
    public function getTotalAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->prix_unitaire * $item->quantite;
        });
    }

    /**
     * Calcul du montant TVA (Accessor)
     */
    public function getTvaAmountAttribute(): float
    {
        return $this->total * self::TVA_RATE;
    }

    /**
     * Calcul du total TTC (Accessor)
     */
    public function getTotalTtcAttribute(): float
    {
        return $this->total + $this->tva_amount;
    }

    /**
     * Nombre total d'articles (Accessor)
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantite');
    }

    /**
     * Vérifier si le panier est vide
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * Vérifier si le panier a expiré
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    
}
