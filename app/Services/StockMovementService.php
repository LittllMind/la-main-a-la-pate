<?php

namespace App\Services;

use App\Models\MouvementStock;
use App\Models\Vinyle;
use App\Models\Fond;
use Illuminate\Support\Facades\Auth;

class StockMovementService
{
    /**
     * Incrémenter le stock d'un fond (créé un mouvement d'entrée)
     */
    public static function incrementerFond(
        Fond $fond, 
        int $quantite, 
        ?string $reference = null,
        ?string $notes = null
    ): MouvementStock {
        $produitType = match($fond->type) {
            'Miroir' => 'miroir',
            'Doré' => 'dore',
            default => 'pochette',
        };
        
        return self::entree(
            $produitType,
            $fond->id,
            $quantite,
            $reference ?? 'FOND-' . str_pad($fond->id, 4, '0', STR_PAD_LEFT),
            $notes ?? "Incrémentation stock {$fond->type}"
        );
    }

    /**
     * Décrémenter le stock d'un fond (créé un mouvement de sortie)
     */
    public static function decrementerFond(
        Fond $fond, 
        int $quantite,
        ?string $reference = null,
        ?string $notes = null
    ): MouvementStock {
        $produitType = match($fond->type) {
            'Miroir' => 'miroir',
            'Doré' => 'dore',
            default => 'pochette',
        };
        
        return self::sortie(
            $produitType,
            $fond->id,
            $quantite,
            $reference ?? 'FOND-' . str_pad($fond->id, 4, '0', STR_PAD_LEFT),
            $notes ?? "Décrémentation stock {$fond->type}"
        );
    }

    /**
     * Enregistrer un mouvement d'entrée de stock
     */
    public static function entree(
        string $produitType,
        int $produitId,
        int $quantite,
        ?string $reference = null,
        ?string $notes = null
    ): MouvementStock {
        return MouvementStock::enregistrer(
            'entree',
            $produitType,
            $produitId,
            $quantite,
            Auth::id() ?? 1, // fallback admin
            $reference,
            $notes
        );
    }

    /**
     * Enregistrer un mouvement de sortie de stock
     * Garde-fou: empêche le stock négatif
     */
    public static function sortie(
        string $produitType,
        int $produitId,
        int $quantite,
        ?string $reference = null,
        ?string $notes = null
    ): MouvementStock {
        // Vérifier le stock disponible avant sortie
        $stockDisponible = self::getStockDisponible($produitType, $produitId);
        
        if ($quantite > $stockDisponible) {
            throw new \InvalidArgumentException(
                "Stock insuffisant: tentative de sortie de {$quantite} unités, " .
                "mais seulement {$stockDisponible} disponibles pour {$produitType} #{$produitId}"
            );
        }
        
        return MouvementStock::enregistrer(
            'sortie',
            $produitType,
            $produitId,
            $quantite,
            Auth::id() ?? 1,
            $reference,
            $notes
        );
    }

    /**
     * Traçage automatique lors création Vinyle
     */
    public static function traceVinyleCreated(Vinyle $vinyle): void
    {
        // Ne pas tracer en environnement de test sans utilisateur authentifié
        if (!Auth::check() && app()->environment('testing')) {
            return;
        }

        self::entree(
            'vinyle',
            $vinyle->id,
            $vinyle->quantite ?? 0,
            $vinyle->reference ?? 'VIN-'.str_pad($vinyle->id, 4, '0', STR_PAD_LEFT),
            'Création vinyle : ' . $vinyle->nom
        );
    }

    /**
     * Traçage automatique lors modification stock Vinyle
     */
    public static function traceVinyleStockChanged(Vinyle $vinyle, int $oldStock, int $newStock): void
    {
        $diff = $newStock - $oldStock;
        
        if ($diff === 0) return;

        if ($diff > 0) {
            self::entree(
                'vinyle',
                $vinyle->id,
                $diff,
                $vinyle->reference ?? 'VIN-'.str_pad($vinyle->id, 4, '0', STR_PAD_LEFT),
                'Mise à jour stock : ' . $vinyle->nom_complet . ' (' . $oldStock . ' → ' . $newStock . ')'
            );
        } else {
            self::sortie(
                'vinyle',
                $vinyle->id,
                abs($diff),
                $vinyle->reference ?? 'VIN-'.str_pad($vinyle->id, 4, '0', STR_PAD_LEFT),
                'Mise à jour stock : ' . $vinyle->nom_complet . ' (' . $oldStock . ' → ' . $newStock . ')'
            );
        }
    }

    /**
     * Traçage automatique lors modification stock Fond
     */
    public static function traceFondStockChanged(Fond $fond, string $typeField, int $oldQty, int $newQty): void
    {
        $produitType = match($typeField) {
            'miroir' => 'miroir',
            'dore' => 'dore',
            'standard' => 'pochette',
            default => 'fond'
        };

        $diff = $newQty - $oldQty;
        
        if ($diff === 0) return;

        $labels = [
            'miroir' => 'Miroir',
            'dore' => 'Doré', 
            'standard' => 'Standard'
        ];

        if ($diff > 0) {
            self::entree(
                $produitType,
                $fond->id,
                $diff,
                'FOND-'.str_pad($fond->id, 4, '0', STR_PAD_LEFT),
                'Mise à jour stock ' . $labels[$typeField] . ' (' . $oldQty . ' → ' . $newQty . ')'
            );
        } else {
            self::sortie(
                $produitType,
                $fond->id,
                abs($diff),
                'FOND-'.str_pad($fond->id, 4, '0', STR_PAD_LEFT),
                'Mise à jour stock ' . $labels[$typeField] . ' (' . $oldQty . ' → ' . $newQty . ')'
            );
        }
    }

    /**
     * Traçage commande validée (sorties)
     */
    public static function traceCommandeValidee($order): void
    {
        foreach ($order->lignes as $ligne) {
            if ($ligne->vinyle) {
                self::sortie(
                    'vinyle',
                    $ligne->vinyle->id,
                    $ligne->quantite,
                    'CMD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                    'Commande #' . $order->id . ' - ' . $ligne->vinyle->titre
                );
            }
            
            if ($ligne->fond) {
                $type = match($ligne->fond_type) {
                    'miroir' => 'miroir',
                    'dore' => 'dore',
                    default => 'pochette'
                };
                
                self::sortie(
                    $type,
                    $ligne->fond->id,
                    $ligne->quantite,
                    'CMD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                    'Commande #' . $order->id . ' - Fond ' . ucfirst($type)
                );
            }
        }
    }

    /**
     * Récupère le stock disponible pour un produit donné
     */
    private static function getStockDisponible(string $produitType, int $produitId): int
    {
        return match($produitType) {
            'miroir', 'dore', 'standard', 'pochette' => \App\Models\Fond::find($produitId)?->quantite ?? 0,
            'vinyle' => \App\Models\Vinyle::find($produitId)?->quantite ?? 0,
            default => 0,
        };
    }
}