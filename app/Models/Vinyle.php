<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Vinyle extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'reference',
        'artiste',
        'modele',
        'genre',
        'style',
        'prix',
        'quantite',
        'seuil_alerte',
    ];

    protected $appends = [
        'image',
        'nom_complet',
    ];

    protected $casts = [
        'prix' => 'decimal:2',
    ];

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(800)
            ->height(800)
            ->format('webp')
            ->nonQueued();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')
            ->useDisk('public');
        // 3 images max (validé dans le controller)
    }

    public function getImageAttribute(): string
    {
        return $this->getFirstMediaUrl('photo', 'medium') ?: '/images/no-image.png';
    }
    
    /**
     * Nom complet : "Artiste - Modèle" (pour affichage)
     */
    public function getNomCompletAttribute(): string
    {
        return $this->artiste . ($this->modele ? ' - ' . $this->modele : '');
    }
    
    /**
     * Le type de fond n'est plus stocké sur le vinyle
     * Il est choisi par le client au moment de l'achat
     * @deprecated
     */
    public function getTypeFondAttribute(): ?string
    {
        return null;
    }
    
    /**
     * @deprecated
     */
    public function getTypeFondLabelAttribute(): ?string
    {
        return null;
    }

    public function isLowStock(): bool
    {
        return $this->quantite > 0 && $this->quantite <= 3;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantite <= 0;
    }

    /**
     * Status du stock avec labels
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->quantite <= 0) {
            return 'Rupture';
        } elseif ($this->quantite <= 3) {
            return 'Faible';
        } else {
            return 'OK';
        }
    }

    /**
     * CSS class pour le statut
     */
    public function getStockStatusClassAttribute(): string
    {
        return match($this->stock_status) {
            'Rupture' => 'badge-danger',
            'Faible' => 'badge-warning',
            default => 'badge-success',
        };
    }
    
    public function ventes()
    {
        return $this->hasMany(LigneVente::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}