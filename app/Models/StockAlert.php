<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'alertable_type',
        'alertable_id',
        'quantite_actuelle',
        'seuil_alerte',
        'statut',
        'derniere_notification_envoyee',
        'resolved_at',
    ];

    protected $casts = [
        'derniere_notification_envoyee' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Relation polymorphique vers Vinyle ou Fond
     */
    public function alertable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope : alertes non résolues
     */
    public function scopeActives($query)
    {
        return $query->where('statut', 'actif');
    }

    /**
     * Scope : filtre par type de produit (Vinyle/Fond)
     */
    public function scopeParType($query, string $type)
    {
        if ($type === 'vinyle') {
            return $query->where('alertable_type', 'App\Models\Vinyle');
        }
        if ($type === 'fond') {
            return $query->where('alertable_type', 'App\Models\Fond');
        }
        return $query;
    }

    /**
     * Scope : filtre par niveau d'urgence
     * - critique: quantite <= 0 (rupture)
     * - faible: 0 < quantite <= seuil_alerte
     * - attention: seuil_alerte < quantite <= seuil_alerte + 2
     */
    public function scopeParNiveau($query, string $niveau)
    {
        return match($niveau) {
            'critique' => $query->where('quantite_actuelle', '<=', 0),
            'faible' => $query->where('quantite_actuelle', '>', 0)
                          ->whereColumn('quantite_actuelle', '<=', 'seuil_alerte'),
            'attention' => $query->whereColumn('quantite_actuelle', '>', 'seuil_alerte')
                           ->whereRaw('quantite_actuelle <= seuil_alerte + 2'),
            default => $query,
        };
    }

    /**
     * Scope : filtre par période
     */
    public function scopeParPeriode($query, string $periode)
    {
        return match($periode) {
            'aujourd_hui' => $query->whereDate('created_at', today()),
            'semaine' => $query->where('created_at', '>=', now()->subWeek()),
            'mois' => $query->where('created_at', '>=', now()->subMonth()),
            'ancien' => $query->where('created_at', '<', now()->subMonth()),
            default => $query,
        };
    }

    /**
     * Scope : recherche par nom de produit
     */
    public function scopeRecherche($query, string $terme)
    {
        if (empty($terme)) return $query;
        
        return $query->whereHasMorph('alertable', [Vinyle::class, Fond::class], function ($q) use ($terme) {
            $q->where('nom', 'like', '%' . $terme . '%');
        });
    }

    /**
     * Scope : tri par priorité (crique d'abord)
     */
    public function scopeTriPriorite($query)
    {
        return $query->orderByRaw('CASE 
            WHEN quantite_actuelle <= 0 THEN 1 
            WHEN quantite_actuelle <= seuil_alerte THEN 2 
            ELSE 3 
        END')
        ->orderBy('created_at', 'desc');
    }

    /**
     * Getters pour niveau d'alerte
     */
    public function getNiveauAttribute(): string
    {
        if ($this->quantite_actuelle <= 0) return 'critique';
        if ($this->quantite_actuelle <= $this->seuil_alerte) return 'faible';
        return 'attention';
    }

    public function getNiveauLabelAttribute(): string
    {
        return match($this->niveau) {
            'critique' => 'Rupture',
            'faible' => 'Stock faible',
            default => 'Attention',
        };
    }

    public function getNiveauColorAttribute(): string
    {
        return match($this->niveau) {
            'critique' => 'red',
            'faible' => 'yellow',
            default => 'blue',
        };
    }

    /**
     * Marquer comme résolu (quand stock réapprovisionné)
     */
    public function marquerResolu(): void
    {
        $this->update([
            'statut' => 'resolu',
            'resolved_at' => now(),
        ]);
    }
}
