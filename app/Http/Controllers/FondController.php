<?php

namespace App\Http\Controllers;

use App\Models\Fond;
use App\Models\MouvementStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Services\StockMovementService;

class FondController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Liste des fonds - accessible Admin et Employé
     */
    public function index()
    {
        // Optimisation: agrégations SQL au lieu de collection PHP
        $totaux = Fond::selectRaw('
            SUM(quantite) as quantite_totale,
            SUM(quantite * prix_achat) as montant_investi,
            SUM(quantite * prix_vente) as valeur_totale,
            SUM(quantite * (prix_vente - prix_achat)) as marge_totale
        ')->first();

        // Chargement paginé avec calculs SQL
        $fonds = Fond::select('*')
            ->selectRaw('quantite * prix_achat as montant_stock')
            ->selectRaw('quantite * prix_vente as valeur_stock')
            ->selectRaw('quantite * (prix_vente - prix_achat) as marge')
            ->selectRaw("CASE 
                WHEN quantite <= 0 THEN 'rupture'
                WHEN quantite <= 5 THEN 'stock_bas'
                ELSE 'ok'
            END as status")
            ->paginate(20)
            ->through(function ($fond) {
                // Accessors calculés pour la vue
                $fond->status_class = match($fond->status) {
                    'rupture' => 'bg-red-100 text-red-800',
                    'stock_bas' => 'bg-yellow-100 text-yellow-800',
                    default => 'bg-green-100 text-green-800',
                };
                return $fond;
            });

        return view('fonds.index', compact('fonds', 'totaux'));
    }

    /**
     * Affichage d'un fond - Admin et Employé (lecture seule)
     */
    public function show(Fond $fond)
    {
        return redirect()->route('fonds.index');
    }

    /**
     * Mise à jour du stock - Admin uniquement
     */
    public function updateStock(Request $request, Fond $fond)
    {
        // Vérification admin - abort 403 pour les tests/protection middleware
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Action réservée aux administrateurs');
        }

        $validated = $request->validate([
            'action' => 'required|in:increment,decrement,set',
            'quantite' => 'required|integer|min:0',
        ]);

        $quantite = $validated['quantite'];

        switch ($validated['action']) {
            case 'increment':
                $fond->quantite += $quantite;
                $message = "+{$quantite} {$fond->type} ajoutés";
                break;
            case 'decrement':
                if ($fond->quantite < $quantite) {
                    return redirect()->route('fonds.index')
                        ->with('error', 'Stock insuffisant pour cette sortie');
                }
                $fond->quantite -= $quantite;
                $message = "-{$quantite} {$fond->type} retirés";
                break;
            case 'set':
                $fond->quantite = $quantite;
                $message = "Stock {$fond->type} fixé à {$quantite}";
                break;
        }

        $fond->save();

        // Création du mouvement de stock (T9 intégré)
        try {
            match($validated['action']) {
                'increment' => StockMovementService::incrementerFond(
                    $fond, 
                    $quantite, 
                    null, 
                    "Incrémentation via dashboard admin"
                ),
                'decrement' => StockMovementService::decrementerFond(
                    $fond, 
                    $quantite, 
                    null, 
                    "Décrémentation via dashboard admin"
                ),
                default => true
            };
        } catch (\Exception $e) {
            // Log l'erreur mais ne bloque pas l'action
            \Log::error('Erreur création mouvement stock: ' . $e->getMessage());
        }

        return redirect()->route('fonds.index')
            ->with('success', $message);
    }

    /**
     * Mise à jour des prix - Admin uniquement
     */
    public function updatePrix(Request $request, Fond $fond)
    {
        // Vérification admin
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Action réservée aux administrateurs');
        }

        $validated = $request->validate([
            'prix_achat' => 'required|numeric|min:0',
            'prix_vente' => 'required|numeric|min:0',
        ]);

        $fond->update($validated);

        return redirect()->route('fonds.index')
            ->with('success', 'Prix mis à jour pour ' . $fond->type);
    }
}
