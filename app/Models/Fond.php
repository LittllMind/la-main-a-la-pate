<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fond extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'visuel',
        'quantite',
        'prix_achat',
        'prix_vente',
    ];

    protected $casts = [
        'prix_achat' => 'decimal:2',
        'prix_vente' => 'decimal:2',
    ];

    /**
     * Montant investi en stock (prix achat × quantité)
     */
    public function getMontantStockAttribute(): float
    {
        return $this->quantite * $this->prix_achat;
    }

    /**
     * Valeur totale du stock (prix vente × quantité)
     */
    public function getValeurStockAttribute(): float
    {
        return $this->quantite * $this->prix_vente;
    }

    /**
     * Marge potentielle sur le stock actuel
     */
    public function getMargeAttribute(): float
    {
        return $this->valeur_stock - $this->montant_stock;
    }

    /**
     * Status du stock
     */
    public function getStatusAttribute(): string
    {
        if ($this->quantite <= 0) {
            return 'Rupture';
        } elseif ($this->quantite <= 5) {
            return 'Alerte';
        } else {
            return 'OK';
        }
    }

    /**
     * CSS class pour le status
     */
    public function getStatusClassAttribute(): string
    {
        return match($this->status) {
            'Rupture' => 'text-red-600 bg-red-100',
            'Alerte' => 'text-orange-600 bg-orange-100',
            default => 'text-green-600 bg-green-100',
        };
    }
}
