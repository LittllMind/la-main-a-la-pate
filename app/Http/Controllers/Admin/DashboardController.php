<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Vinyle;
use App\Models\Fond;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Affiche le tableau de bord admin avec les statistiques globales
     */
    public function index()
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Statistiques des ventes (mois en cours)
        $ventesMois = Order::where('statut', 'livree')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total') ?? 0;

        // Nombre de commandes en cours (en_attente, en_preparation, prete)
        $commandesEnCours = Order::whereIn('statut', ['en_attente', 'en_preparation', 'prete'])
            ->count();

        // Valeur du stock vinyles (quantite * prix de vente)
        $valeurStockVinyles = Vinyle::query()
            ->selectRaw('SUM(quantite * prix) as valeur')
            ->value('valeur') ?? 0;

        // Valeur du stock fonds (quantite * prix_vente)
        $valeurStockFonds = Fond::query()
            ->selectRaw('SUM(quantite * prix_vente) as valeur')
            ->value('valeur') ?? 0;

        // Total unites en stock
        $totalVinyles = Vinyle::sum('quantite') ?? 0;
        $totalFonds = Fond::sum('quantite') ?? 0;

        // Alertes stock faible (vinyles avec quantite entre 1 et seuil_alerte)
        $alertesVinyles = Vinyle::whereBetween('quantite', [1, 3])->count();

        // Ruptures de stock
        $rupturesVinyles = Vinyle::where('quantite', '<=', 0)->count();
        $rupturesFonds = Fond::where('quantite', '<=', 0)->count();

        // Dernieres commandes
        $dernieresCommandes = Order::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ventes des 6 derniers mois (pour graphique) - compatible SQLite
        $ventesMensuelles = collect(range(5, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();
            return [
                'mois' => $date->format('M Y'),
                'montant' => Order::where('statut', 'livree')
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('total') ?? 0,
            ];
        });

        return view('admin.dashboard', compact(
            'ventesMois',
            'commandesEnCours',
            'valeurStockVinyles',
            'valeurStockFonds',
            'totalVinyles',
            'totalFonds',
            'alertesVinyles',
            'rupturesVinyles',
            'rupturesFonds',
            'dernieresCommandes',
            'ventesMensuelles'
        ));
    }

    /**
     * API JSON pour les statistiques (utilisee par les graphiques)
     */
    public function statsApi()
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        return response()->json([
            'ventes_mois' => Order::where('statut', 'livree')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->sum('total') ?? 0,
            'commandes_en_cours' => Order::whereIn('statut', ['en_attente', 'en_preparation', 'prete'])->count(),
            'valeur_stock_vinyles' => Vinyle::query()->selectRaw('SUM(quantite * prix) as valeur')->value('valeur') ?? 0,
            'valeur_stock_fonds' => Fond::query()->selectRaw('SUM(quantite * prix_vente) as valeur')->value('valeur') ?? 0,
            'total_vinyles' => Vinyle::sum('quantite') ?? 0,
            'total_fonds' => Fond::sum('quantite') ?? 0,
            'alertes_stock' => Vinyle::whereBetween('quantite', [1, 3])->count(),
        ]);
    }

    /**
     * API JSON pour les graphiques temporels (ventes 12 mois, evolution stock)
     */
    public function chartsApi()
    {
        // Ventes sur les 12 derniers mois (exclut commandes annulees)
        $ventes12Mois = collect(range(11, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();
            
            return [
                'mois' => $date->format('Y-m'),
                'montant' => Order::where('statut', 'livree')
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('total') ?? 0,
            ];
        });

        // Evolution du stock vinyles (12 derniers mois)
        // Approximation: stock actuel + ventes depuis cette date
        $evolutionStockVinyles = collect(range(11, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            
            // Calculer les ventes depuis cette date
            $ventesDepuis = DB::table('ligne_ventes')
                ->join('ventes', 'ligne_ventes.vente_id', '=', 'ventes.id')
                ->where('ventes.created_at', '>=', $date)
                ->sum('ligne_ventes.quantite') ?? 0;
            
            $stockActuel = Vinyle::sum('quantite') ?? 0;
            $stockHistorique = $stockActuel + $ventesDepuis;
            
            return [
                'mois' => $date->format('Y-m'),
                'quantite' => $stockHistorique,
            ];
        });

        // Evolution du stock fonds (12 derniers mois)
        $evolutionStockFonds = collect(range(11, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            
            // Approximation similaire pour les fonds
            $ventesFondsDepuis = DB::table('ligne_ventes')
                ->whereNotNull('fond')
                ->where('created_at', '>=', $date)
                ->sum('quantite') ?? 0;
            
            $stockFondsActuel = Fond::sum('quantite') ?? 0;
            $stockFondsHistorique = $stockFondsActuel + $ventesFondsDepuis;
            
            return [
                'mois' => $date->format('Y-m'),
                'quantite' => $stockFondsHistorique,
            ];
        });

        return response()->json([
            'ventes_12_mois' => $ventes12Mois,
            'evolution_stock_vinyles' => $evolutionStockVinyles,
            'evolution_stock_fonds' => $evolutionStockFonds,
        ]);
    }
}
