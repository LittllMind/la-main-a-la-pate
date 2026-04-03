<?php

namespace App\Http\Controllers;

use App\Models\Fond;
use App\Models\StockAlert;
use App\Models\Vinyle;
use Illuminate\Http\Request;

class StockAlertController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,employe']);
    }

    /**
     * Affiche la liste des alertes de stock avec filtres avancés
     */
    public function index(Request $request)
    {
        // Récupérer les paramètres de filtre
        $filtres = [
            'type' => $request->input('type', 'tous'),
            'produit' => $request->input('produit', 'tous'),
            'statut' => $request->input('statut', 'actif'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'search' => $request->input('search'),
            'sort' => $request->input('sort', 'date_desc'),
        ];

        // Construction de la requête avec filtres
        $query = StockAlert::with('alertable');

        // Filtre par statut
        if ($filtres['statut'] !== 'tous') {
            $query->where('statut', $filtres['statut']);
        }

        // Filtre par type d'alerte (rupture/faible)
        if ($filtres['type'] === 'rupture') {
            $query->where('quantite_actuelle', '<=', 0);
        } elseif ($filtres['type'] === 'faible') {
            $query->whereRaw('quantite_actuelle > 0 AND quantite_actuelle <= seuil_alerte');
        }

        // Filtre par type de produit (polymorphe)
        if ($filtres['produit'] === 'vinyle') {
            $query->where('alertable_type', 'App\\Models\\Vinyle');
        } elseif ($filtres['produit'] === 'fond') {
            $query->where('alertable_type', 'App\\Models\\Fond');
        }

        // Filtre par dates
        if ($filtres['date_debut']) {
            $query->whereDate('created_at', '>=', $filtres['date_debut']);
        }
        if ($filtres['date_fin']) {
            $query->whereDate('created_at', '<=', $filtres['date_fin']);
        }

        // Filtre par recherche (nom/ref produit)
        if ($filtres['search']) {
            $query->whereHas('alertable', function ($q) use ($filtres) {
                $q->where('nom', 'like', '%' . $filtres['search'] . '%')
                  ->orWhere('reference', 'like', '%' . $filtres['search'] . '%')
                  ->orWhere('artiste', 'like', '%' . $filtres['search'] . '%');
            });
        }

        // Tri
        switch ($filtres['sort']) {
            case 'date_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'date_desc':
                $query->orderBy('created_at', 'desc');
                break;
            case 'produit':
                $query->orderBy('alertable_type')->orderBy('created_at', 'desc');
                break;
            case 'type':
                $query->orderByRaw('CASE 
                    WHEN quantite_actuelle <= 0 THEN 1 
                    WHEN quantite_actuelle <= seuil_alerte THEN 2 
                    ELSE 3 END')
                      ->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $alerts = $query->paginate(20)->withQueryString();

        // Calculs stats (tjs basé sur données live)
        $stats = $this->calculateStats();

        // Données pour les alertes en direct
        $criticalCount = Vinyle::where('quantite', '<=', 0)->count();
        $lowStockItems = Vinyle::whereRaw('quantite > 0 AND quantite <= seuil_alerte')->get();
        $outOfStockItems = Vinyle::where('quantite', '<=', 0)->get();

        return view('stock-alerts.index', compact(
            'alerts',
            'filtres',
            'stats',
            'criticalCount',
            'lowStockItems',
            'outOfStockItems'
        ));
    }

    /**
     * Calcule les statistiques des alertes
     */
    private function calculateStats()
    {
        return [
            'total_actives' => StockAlert::where('statut', 'actif')->count(),
            'total_resolues' => StockAlert::where('statut', 'resolu')->count(),
            'ruptures' => StockAlert::where('statut', 'actif')
                ->where('quantite_actuelle', '<=', 0)
                ->count(),
            'faibles' => StockAlert::where('statut', 'actif')
                ->whereRaw('quantite_actuelle > 0 AND quantite_actuelle <= seuil_alerte')
                ->count(),
            'vinyles' => StockAlert::where('statut', 'actif')
                ->where('alertable_type', 'App\\Models\\Vinyle')
                ->count(),
            'fonds' => StockAlert::where('statut', 'actif')
                ->where('alertable_type', 'App\\Models\\Fond')
                ->count(),
            'aujourdhui' => StockAlert::whereDate('created_at', today())->count(),
            'cette_semaine' => StockAlert::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];
    }

    /**
     * Marque une alerte comme résolue
     */
    public function resolve(StockAlert $alert)
    {
        $alert->marquerResolu();

        return redirect()
            ->route('stock-alerts.index')
            ->with('success', 'Alerte marquée comme résolue');
    }

    /**
     * Historique des alertes résolues avec filtres
     */
    public function history(Request $request)
    {
        $query = StockAlert::with('alertable')
            ->where('statut', 'resolu');

        // Filtre par type de produit
        if ($request->produit === 'vinyle') {
            $query->where('alertable_type', 'App\\Models\\Vinyle');
        } elseif ($request->produit === 'fond') {
            $query->where('alertable_type', 'App\\Models\\Fond');
        }

        // Filtre par dates
        if ($request->date_debut) {
            $query->whereDate('resolved_at', '>=', $request->date_debut);
        }
        if ($request->date_fin) {
            $query->whereDate('resolved_at', '<=', $request->date_fin);
        }

        $alerts = $query->orderBy('resolved_at', 'desc')->paginate(20);

        return view('stock-alerts.history', compact('alerts'));
    }

    /**
     * Créer manuellement une alerte (optionnel)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'alertable_type' => 'required|string',
            'alertable_id' => 'required|integer',
            'quantite_actuelle' => 'required|integer|min:0',
            'seuil_alerte' => 'required|integer|min:1',
        ]);

        // Vérifie si alerte existe déjà
        $exists = StockAlert::where('alertable_type', $validated['alertable_type'])
            ->where('alertable_id', $validated['alertable_id'])
            ->where('statut', 'actif')
            ->exists();

        if ($exists) {
            return back()->with('warning', 'Une alerte existe déjà pour cet article');
        }

        StockAlert::create([
            'alertable_type' => $validated['alertable_type'],
            'alertable_id' => $validated['alertable_id'],
            'quantite_actuelle' => $validated['quantite_actuelle'],
            'seuil_alerte' => $validated['seuil_alerte'],
            'statut' => 'actif',
        ]);

        return back()->with('success', 'Alerte créée avec succès');
    }

    /**
     * Export des alertes filtrées (CSV)
     */
    public function export(Request $request)
    {
        $filename = 'alertes_stock_' . now()->format('Y-m-d_His') . '.csv';
        
        // Reconstruire la requête avec les mêmes filtres
        $query = StockAlert::with('alertable');
        
        if ($request->type === 'rupture') {
            $query->where('quantite_actuelle', '<=', 0);
        } elseif ($request->type === 'faible') {
            $query->whereRaw('quantite_actuelle > 0 AND quantite_actuelle <= seuil_alerte');
        }
        
        if ($request->statut) {
            $query->where('statut', $request->statut);
        }
        
        $alerts = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($alerts) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Produit', 'Type', 'Quantité', 'Seuil', 'Statut', 'Résolu le']);
            
            foreach ($alerts as $alert) {
                fputcsv($handle, [
                    $alert->created_at->format('d/m/Y H:i'),
                    $alert->alertable ? $alert->alertable->nom : 'N/A',
                    $alert->quantite_actuelle <= 0 ? 'Rupture' : 'Stock Faible',
                    $alert->quantite_actuelle,
                    $alert->seuil_alerte,
                    $alert->statut,
                    $alert->resolved_at ? $alert->resolved_at->format('d/m/Y H:i') : '-',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
