<?php
// app/Models/CartItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'vinyle_id',
        'fond_id',
        'quantite',
        'prix_unitaire',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire' => 'decimal:2',
    ];

    /**
     * Relation : Un item appartient à un panier
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Relation : Un item concerne un vinyle
     */
    public function vinyle(): BelongsTo
    {
        return $this->belongsTo(Vinyle::class);
    }

    /**
     * Relation : Un item peut avoir un fond (optionnel)
     */
    public function fond(): BelongsTo
    {
        return $this->belongsTo(Fond::class);
    }

    /**
     * Calcul du sous-total de l'item
     */
    public function subtotal(): float
    {
        return $this->prix_unitaire * $this->quantite;
    }

    /**
     * Vérifier la disponibilité du stock
     */
    public function hasStock(): bool
    {
        if (!$this->vinyle) {
            return false;
        }

        return $this->vinyle->quantite >= $this->quantite;
    }
}
