<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,employe']);
    }

    /**
     * Liste toutes les commandes (admin)
     */
    public function index(Request $request)
    {
        $query = Order::with(['items.vinyle', 'user'])
            ->orderBy('created_at', 'desc');

        // Filtre par statut
        if ($request->has('statut') && $request->statut) {
            $query->where('statut', $request->statut);
        }

        // Filtre par source
        if ($request->has('source') && $request->source) {
            $query->where('source', $request->source);
        }

        $orders = $query->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Afficher une commande
     */
    public function show(Order $order)
    {
        $order->load(['items.vinyle', 'user', 'vente.lignes.vinyle']);
        return view('admin.orders.show', compact('order'));
    }

    /**
     * Mettre à jour le statut d'une commande
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'statut' => 'required|string|in:en_attente,en_preparation,prete,livree,annulee,completed',
        ]);

        $oldStatut = $order->statut;
        $newStatut = $validated['statut'];

        // Mettre à jour les timestamps selon le statut
        $updates = ['statut' => $newStatut];

        switch ($newStatut) {
            case 'en_preparation':
                $updates['preparee_at'] = now();
                break;
            case 'prete':
                $updates['prete_at'] = now();
                break;
            case 'livree':
                $updates['livree_at'] = now();
                break;
            case 'annulee':
                $updates['annulee_at'] = now();
                break;
            case 'completed':
                $updates['validee_at'] = now();
                break;
        }

        $order->update($updates);

        return redirect()
            ->back()
            ->with('success', "Statut changé de '{$oldStatut}' à '{$newStatut}'");
    }

    /**
     * Annuler une commande
     */
    public function cancel(Order $order)
    {
        if ($order->statut === 'annulee') {
            return redirect()->back()->with('error', 'Commande déjà annulée');
        }

        $order->update([
            'statut' => 'annulee',
            'annulee_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Commande annulée');
    }
}
