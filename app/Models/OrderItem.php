<?php
// app/Models/OrderItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'vinyle_id',
        'fond_id',
        'titre_vinyle',
        'artiste_vinyle',
        'reference_vinyle',
        'quantite',
        'prix_unitaire',
        'total',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Relation : Un item appartient à une commande
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relation : Un item concerne un vinyle (peut être null si supprimé)
     */
    public function vinyle(): BelongsTo
    {
        return $this->belongsTo(Vinyle::class);
    }

    /**
     * Relation : Un item peut avoir un fond
     */
    public function fond(): BelongsTo
    {
        return $this->belongsTo(Fond::class);
    }
}
