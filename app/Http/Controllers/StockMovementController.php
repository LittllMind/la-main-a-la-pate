<?php

namespace App\Http\Controllers;

use App\Models\MouvementStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    /**
     * Afficher l'historique des mouvements de stock
     */
    public function index(Request $request)
    {
        $query = MouvementStock::with('user')
            ->orderBy('date_mouvement', 'desc');

        // Filtre par type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filtre par produit_type
        if ($request->filled('produit_type')) {
            $query->where('produit_type', $request->produit_type);
        }

        // Filtre par date début
        if ($request->filled('date_debut')) {
            $query->where('date_mouvement', '>=', $request->date_debut . ' 00:00:00');
        }

        // Filtre par date fin
        if ($request->filled('date_fin')) {
            $query->where('date_mouvement', '<=', $request->date_fin . ' 23:59:59');
        }

        // Filtre par référence
        if ($request->filled('search')) {
            $query->where('reference', 'like', '%' . $request->search . '%');
        }

        $mouvements = $query->paginate(25)->withQueryString();

        // Statistics pour le dashboard
        $stats = [
            'total_entrees' => MouvementStock::entrees()->sum('quantite') ?: 0,
            'total_sorties' => MouvementStock::sorties()->sum('quantite') ?: 0,
            'aujourdhui' => MouvementStock::whereDate('date_mouvement', today())->count(),
        ];

        // Options pour les filtres
        $types = ['entree' => 'Entrées', 'sortie' => 'Sorties'];
        $produitTypes = [
            'vinyle' => 'Vinyles',
            'miroir' => 'Fonds Miroir',
            'dore' => 'Fonds Dorés',
            'pochette' => 'Pochettes',
        ];

        return view('mouvements.index', compact(
            'mouvements',
            'stats',
            'types',
            'produitTypes'
        ));
    }

    /**
     * Export CSV des mouvements
     */
    public function export(Request $request)
    {
        $query = MouvementStock::with('user')
            ->orderBy('date_mouvement', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_debut')) {
            $query->where('date_mouvement', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->where('date_mouvement', '<=', $request->date_fin);
        }

        $mouvements = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="mouvements_stock_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($mouvements) {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8 pour Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'Date',
                'Type',
                'Produit',
                'ID Produit',
                'Quantité',
                'Utilisateur',
                'Référence',
                'Notes'
            ]);

            // Data
            foreach ($mouvements as $m) {
                fputcsv($file, [
                    $m->date_mouvement->format('d/m/Y H:i'),
                    $m->type === 'entree' ? 'Entrée' : 'Sortie',
                    $m->produit_libelle,
                    $m->produit_id,
                    $m->quantite,
                    $m->user?->name ?? 'Système',
                    $m->reference ?? '',
                    $m->notes ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}