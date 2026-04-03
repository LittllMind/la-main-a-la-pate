<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MouvementStock extends Model
{
    use HasFactory;

    protected $table = 'mouvements_stock';

    protected $fillable = [
        'type',
        'produit_type',
        'produit_id',
        'quantite',
        'date_mouvement',
        'user_id',
        'reference',
        'notes',
    ];

    protected $casts = [
        'date_mouvement' => 'datetime',
        'quantite' => 'integer',
    ];

    // ========== SCOPES ==========

    public function scopeEntrees($query)
    {
        return $query->where('type', 'entree');
    }

    public function scopeSorties($query)
    {
        return $query->where('type', 'sortie');
    }

    public function scopeParProduit($query, string $type, int $id)
    {
        return $query->where('produit_type', $type)->where('produit_id', $id);
    }

    public function scopeParPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_mouvement', [$debut, $fin]);
    }

    // ========== RELATIONS ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ========== METHODES ==========

    /**
     * Créer un mouvement et mettre à jour le stock produit (à implémenter dans services)
     * 
     * @param string $type 'entree'|'sortie'
     * @param string $produitType 'vinyle'|'miroir'|'dore'|'pochette'
     * @param int $produitId
     * @param int $quantite
     * @param int $userId
     * @param string|null $reference
     * @param string|null $notes
     * @return self
     */
    public static function enregistrer(
        string $type,
        string $produitType,
        int $produitId,
        int $quantite,
        int $userId,
        ?string $reference = null,
        ?string $notes = null
    ): self {
        return self::create([
            'type' => $type,
            'produit_type' => $produitType,
            'produit_id' => $produitId,
            'quantite' => $quantite,
            'date_mouvement' => now(),
            'user_id' => $userId,
            'reference' => $reference,
            'notes' => $notes,
        ]);
    }

    /**
     * Récupérer le libellé du produit selon son type
     */
    public function getProduitLibelleAttribute(): string
    {
        return match($this->produit_type) {
            'vinyle' => 'Vinyle',
            'miroir' => 'Fond Miroir',
            'dore' => 'Fond Doré',
            'pochette' => 'Pochette',
            default => 'Produit inconnu',
        };
    }

    /**
     * Badge coloré pour le type
     */
    public function getTypeBadgeAttribute(): string
    {
        $classes = [
            'entree' => 'bg-green-100 text-green-800',
            'sortie' => 'bg-red-100 text-red-800',
        ];
        $labels = [
            'entree' => 'Entrée',
            'sortie' => 'Sortie',
        ];
        
        return sprintf(
            '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full %s">%s</span>',
            $classes[$this->type] ?? 'bg-gray-100',
            $labels[$this->type] ?? $this->type
        );
    }
}