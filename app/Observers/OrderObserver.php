<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\StockMovementService;

class OrderObserver
{
    /**
     * Observer le changement de statut de commande
     * Déclencher les mouvements de sortie quand validée
     */
    public function updating(Order $order): void
    {
        // Si le statut passe à 'validee' ou 'prete' (prête à être retirée/enlevée)
        if ($order->isDirty('statut') && in_array($order->statut, ['validee', 'prete', 'livree'])) {
            $oldStatut = $order->getOriginal('statut');
            
            // Ne tracer qu'une seule fois (passage en cours → terminée)
            if (!in_array($oldStatut, ['validee', 'prete', 'livree'])) {
                $this->tracerSortieStock($order);
            }
        }
    }

    /**
     * Traçage des sorties de stock pour une commande
     */
    private function tracerSortieStock(Order $order): void
    {
        foreach ($order->items as $item) {
            // Sortie vinyle
            if ($item->vinyle) {
                StockMovementService::sortie(
                    'vinyle',
                    $item->vinyle->id,
                    $item->quantite,
                    $order->numero_commande ?? 'CMD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                    'Vente commande - ' . ($item->titre_vinyle ?? $item->vinyle->titre)
                );
            }

            // Sortie fond (si présent)
            if ($item->fond) {
                $type = match($item->fond->type ?? 'standard') {
                    'miroir' => 'miroir',
                    'dore', 'doré' => 'dore',
                    default => 'pochette',
                };

                StockMovementService::sortie(
                    $type,
                    $item->fond->id,
                    $item->quantite,
                    $order->numero_commande ?? 'CMD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                    'Vente commande - Fond ' . ucfirst($type)
                );
            }
        }
    }

    /**
     * Annulation = retour en stock (optionnel)
     */
    public function updatingCanceled(Order $order): void
    {
        if ($order->isDirty('statut') && $order->statut === 'annulee') {
            $oldStatut = $order->getOriginal('statut');

            // Si on annule une commande déjà validée
            if (in_array($oldStatut, ['validee', 'prete'])) {
                $this->tracerRetourStock($order);
            }
        }
    }

    /**
     * Traçage des retours en stock (annulation)
     */
    private function tracerRetourStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->vinyle) {
                StockMovementService::entree(
                    'vinyle',
                    $item->vinyle->id,
                    $item->quantite,
                    'RET-' . ($order->numero_commande ?? 'CMD-' . $order->id),
                    'Annulation commande - Retour stock : ' . ($item->titre_vinyle ?? $item->vinyle->titre)
                );
            }
        }
    }
}
