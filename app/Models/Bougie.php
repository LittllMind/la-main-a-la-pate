<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bougie extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'parfum',
        'nom',
        'collection',
        'format',
        'type_cire',
        'temps_brulure',
        'notes',
        'prix',
        'quantite',
        'seuil_alerte',
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'quantite' => 'integer',
        'seuil_alerte' => 'integer',
        'temps_brulure' => 'integer',
    ];
}
