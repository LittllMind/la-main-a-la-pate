<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LigneVente extends Model
{
    use HasFactory;

    protected $fillable = [
        'vente_id',
        'vinyle_id',
        'titre_vinyle',  // Snapshot du titre au moment de la vente
        'quantite',
        'prix_unitaire',
        'total',
        'fond',
    ];

    /**
     * Vente associée
     */
    public function vente(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    /**
     * Vinyle associé (peut être null si supprimé)
     */
    public function vinyle(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Vinyle::class)->withDefault([
            'nom' => $this->titre_vinyle ?? 'Vinyle supprimé',
            'titre' => $this->titre_vinyle ?? 'Vinyle supprimé',
        ]);
    }

    /**
     * Titre affichable (vinyle ou snapshot)
     * @return string
     */
    public function getTitreAttribute(): string
    {
        return $this->vinyle?->nom ?? $this->titre_vinyle ?? 'Vinyle supprimé';
    }

    /**
     * Titre du vinyle (pour compatibilité API ancienne)
     * @return string
     */
    public function getVinyleTitreAttribute(): string
    {
        return $this->getTitreAttribute();
    }

    /**
     * Boot du modèle
     * Ajoute automatiquement le snapshot du titre lors de la création
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (LigneVente $ligneVente): void {
            // Si pas de titre_vinyle mais vinyle_id présent, snapshot le titre
            if (empty($ligneVente->titre_vinyle) && $ligneVente->vinyle_id) {
                $vinyle = Vinyle::find($ligneVente->vinyle_id);
                if ($vinyle) {
                    $ligneVente->titre_vinyle = $vinyle->nom ?? $vinyle->titre ?? 'Vinyle #' . $vinyle->id;
                }
            }
        });
    }
}