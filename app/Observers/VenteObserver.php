<?php

namespace App\Observers;

use App\Models\Vente;
use App\Models\Order;

class VenteObserver
{
    /**
     * Handle the Vente "created" event.
     */
    public function created(Vente $vente): void
    {
        // Créer automatiquement une commande liée pour les ventes kiosque
        $order = Order::create([
            'numero_commande' => Order::generateNumero(),
            'vente_id' => $vente->id,
            'nom' => 'Vente Kiosque',
            'prenom' => '#' . $vente->id,
            'email' => 'kiosque@localhost',
            'telephone' => '0000000000',
            'adresse' => 'Kiosque',
            'code_postal' => '00000',
            'ville' => 'Kiosque',
            'total' => $vente->total,
            'statut' => 'payee',
            'source' => 'marche',
            'mode_paiement_marche' => match($vente->mode_paiement) {
                'especes' => 'cash',
                'carte' => 'cb_terminal',
                'cheque' => 'cheque',
                default => 'cash',
            },
        ]);
    }

    /**
     * Handle the Vente "updated" event.
     */
    public function updated(Vente $vente): void
    {
        // Mettre à jour le total de la commande liée si la vente change
        if ($vente->order) {
            $vente->order->update([
                'total' => $vente->total,
            ]);
        }
    }

    /**
     * Handle the Vente "deleted" event.
     */
    public function deleted(Vente $vente): void
    {
        // Optionnel : annuler la commande liée
        if ($vente->order) {
            $vente->order->update(['statut' => 'annulee']);
        }
    }
}
