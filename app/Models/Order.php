<?php
// app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_commande',
        'vente_id',
        'user_id',
        'nom',
        'prenom',
        'email',
        'telephone',
        'adresse',
        'code_postal',
        'ville',
        'total',
        'statut',
        'status',
        'notes',
        'notes_client',
        'validee_at',
        'preparee_at',
        'prete_at',
        'livree_at',
        'annulee_at',
        'shipping_nom',
        'shipping_prenom',
        'shipping_email',
        'shipping_telephone',
        'shipping_adresse',
        'shipping_code_postal',
        'shipping_ville',
        'shipping_pays',
        'shipping_instructions',
        'billing_nom',
        'billing_prenom',
        'billing_email',
        'billing_telephone',
        'billing_adresse',
        'billing_code_postal',
        'billing_ville',
        'billing_pays',
        // Mode marché
        'source',
        'mode_paiement_marche',
        'notes_vendeur',
        'affichage_client',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'validee_at' => 'datetime',
        'preparee_at' => 'datetime',
        'prete_at' => 'datetime',
        'livree_at' => 'datetime',
        'annulee_at' => 'datetime',
    ];

    /**
     * Générer un numéro de commande unique
     */
    public static function generateNumero(): string
    {
        $year = date('Y');
        $lastOrder = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastOrder ? ((int) substr($lastOrder->numero_commande, -4)) + 1 : 1;

        return sprintf('CMD-%s-%04d', $year, $number);
    }

    /**
     * Relation : Une commande peut être liée à une vente kiosque
     */
    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    /**
     * Relation : Une commande appartient à un utilisateur (optionnel)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Une commande contient plusieurs items
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Nom complet du client
     */
    public function nomComplet(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    /**
     * Badge coloré du statut
     */
    public function statutBadge(): string
    {
        return match($this->statut) {
            'en_attente' => '<span class="badge badge-warning">🟡 En attente</span>',
            'en_preparation' => '<span class="badge badge-info">🔵 En préparation</span>',
            'prete' => '<span class="badge badge-success">🟢 Prête</span>',
            'livree' => '<span class="badge badge-secondary">⚪ Livrée</span>',
            'annulee' => '<span class="badge badge-danger">🔴 Annulée</span>',
            default => $this->statut,
        };
    }

    /**
     * Libellé du statut
     */
    public function statutLabel(): string
    {
        return match($this->statut) {
            'en_attente' => 'En attente',
            'en_preparation' => 'En préparation',
            'prete' => 'Prête',
            'livree' => 'Livrée',
            'annulee' => 'Annulée',
            default => $this->statut,
        };
    }

    /**
     * Scopes pour filtrage
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeEnCours($query)
    {
        return $query->whereIn('statut', ['en_attente', 'en_preparation', 'prete']);
    }

    public function scopeTerminees($query)
    {
        return $query->whereIn('statut', ['livree', 'annulee']);
    }
}
