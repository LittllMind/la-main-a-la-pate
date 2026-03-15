<?php

namespace App\Http\Controllers;

use App\Models\Vinyle;
use App\Models\Vente;
use App\Models\LigneVente;
use App\Models\Fond;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        $periode = $request->get('periode', '3m');
        
        // Cache key basé sur la période et la date (5min de cache)
        $cacheKey = 'stats_dashboard_' . $periode . '_' . now()->format('Y-m-d_H');
        
        $cachedStats = Cache::remember($cacheKey, 300, function () use ($periode, $request) {
            return $this->calculateStats($periode, $request);
        });

        return view('stats', $cachedStats);
    }

    /**
     * Calcul des stats (sorti pour permettre le cache)
     * Optimisé : requêtes agrégées en batch pour éviter N+1
     */
    private function calculateStats($periode, $request)
    {
        // Période choisie
        switch ($periode) {
            case '30j':
                $startDate      = now()->subDays(30)->startOfDay();
                $sqlGroupFormat = '%Y-%m-%d';
                $grouping       = 'day';
                $periodeLabel   = '30 derniers jours';
                break;

            case '12m':
                $startDate      = now()->subMonthsNoOverflow(12)->startOfMonth();
                $sqlGroupFormat = '%Y-%m';
                $grouping       = 'month';
                $periodeLabel   = '12 derniers mois';
                break;

            case 'all':
                $startDate      = null;
                $sqlGroupFormat = '%Y-%m';
                $grouping       = 'month';
                $periodeLabel   = 'depuis le début';
                break;

            case '3m':
            default:
                $startDate      = now()->subMonthsNoOverflow(3)->startOfDay();
                $sqlGroupFormat = '%Y-%m-%d';
                $grouping       = 'day';
                $periode        = '3m';
                $periodeLabel   = '3 derniers mois';
                break;
        }

        // ======================================================
        // 2. COÛTS UNITAIRES
        // ======================================================
        $coutAchatVinyle = 8.50;
        $coutAchatFond   = 3.00;

        // ======================================================
        // 3. STATS CATALOGUE & STOCK (requêtes agrégées en UNE SEULE requête)
        // ======================================================

        // --- VINYLES : Toutes les stats en une requête ---
        $vinyleStats = Vinyle::selectRaw('
            COUNT(*) as total_models,
            COALESCE(SUM(quantite), 0) as total_quantite,
            COALESCE(SUM(prix * quantite), 0) as valeur_stock
        ')->first();

        $totalVinyles = $vinyleStats->total_models;
        $totalQuantiteVinyles = $vinyleStats->total_quantite;
        $quantiteVinylesStock = $totalQuantiteVinyles;
        $valeurStock = $vinyleStats->valeur_stock;
        $valeurStockAchatVinyles = $quantiteVinylesStock * $coutAchatVinyle;

        // CA total historique
        $chiffreAffairesTotal = Vente::sum('total') ?? 0;
        $caStockPotentielVinyles = $valeurStock;
        $caTotalPossibleVinyles = $chiffreAffairesTotal + $caStockPotentielVinyles;

        // --- FONDS : Toutes les stats en une requête ---
        $fondStats = Fond::selectRaw("
            COALESCE(SUM(CASE WHEN type = 'miroir' THEN quantite ELSE 0 END), 0) as stock_miroir,
            COALESCE(SUM(CASE WHEN type = 'dore' THEN quantite ELSE 0 END), 0) as stock_dore
        ")->first();

        $stockMiroir = $fondStats->stock_miroir;
        $stockDore = $fondStats->stock_dore;
        $quantiteFondsMiroirStock = $stockMiroir;
        $quantiteFondsDoreStock = $stockDore;
        $quantiteFondsStockTotal = $stockMiroir + $stockDore;
        $valeurStockFonds = $quantiteFondsStockTotal * $coutAchatFond;

        // ======================================================
        // 4. VINYLES – HISTORIQUE (requêtes agrégées)
        // ======================================================

        $quantiteVinylesVendus = LigneVente::sum('quantite') ?? 0;
        $quantiteVinylesAchetes = $quantiteVinylesStock + $quantiteVinylesVendus;
        $coutAchatVinylesVendus = $quantiteVinylesVendus * $coutAchatVinyle;
        $investissementTotalVinyles = $quantiteVinylesAchetes * $coutAchatVinyle;

        // ======================================================
        // 5. FONDS – HISTORIQUE (requêtes agrégées en une seule)
        // ======================================================

        $fondVentesStats = LigneVente::selectRaw("
            COALESCE(SUM(CASE WHEN fond = 'miroir' THEN quantite ELSE 0 END), 0) as miroir_vendus,
            COALESCE(SUM(CASE WHEN fond = 'dore' THEN quantite ELSE 0 END), 0) as dore_vendus
        ")->first();

        $quantiteFondsMiroirVendus = $fondVentesStats->miroir_vendus;
        $quantiteFondsDoreVendus = $fondVentesStats->dore_vendus;
        $quantiteFondsVendusTotal = $quantiteFondsMiroirVendus + $quantiteFondsDoreVendus;

        $quantiteFondsMiroirAchetes = $quantiteFondsMiroirStock + $quantiteFondsMiroirVendus;
        $quantiteFondsDoreAchetes = $quantiteFondsDoreStock + $quantiteFondsDoreVendus;
        $quantiteFondsAchetesTotal = $quantiteFondsMiroirAchetes + $quantiteFondsDoreAchetes;

        $coutAchatFondsVendus = $quantiteFondsVendusTotal * $coutAchatFond;
        $investissementTotalFonds = $quantiteFondsAchetesTotal * $coutAchatFond;

        // ======================================================
        // 6. MARGES GLOBALES
        // ======================================================

        $coutTotalHistorique = $coutAchatVinylesVendus + $coutAchatFondsVendus;
        $margeBruteTotale = $chiffreAffairesTotal - $coutTotalHistorique;
        $tauxMargeBruteTotale = $chiffreAffairesTotal > 0
            ? ($margeBruteTotale / $chiffreAffairesTotal) * 100
            : 0;

        $margeMoyenneParVinyle = $quantiteVinylesVendus > 0
            ? $margeBruteTotale / $quantiteVinylesVendus
            : 0;

        $margePotentielleStock = $valeurStock - $valeurStockAchatVinyles;

        // ======================================================
        // 7. STATS VENTES SUR LA PÉRIODE (requêtes agrégées)
        // ======================================================

        // UNE requête pour toutes les stats de la période
        $ventesPeriodeStats = Vente::selectRaw('
            COUNT(*) as total_ventes,
            COALESCE(SUM(total), 0) as chiffre_affaires,
            MIN(created_at) as date_premiere_vente
        ')
        ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
        ->first();

        $totalVentes = $ventesPeriodeStats->total_ventes;
        $chiffreAffaires = $ventesPeriodeStats->chiffre_affaires;

        // CA moyen par jour
        if ($startDate) {
            $dateDebut = $startDate;
        } else {
            $dateDebut = $ventesPeriodeStats->date_premiere_vente
                ? \Carbon\Carbon::parse($ventesPeriodeStats->date_premiere_vente)
                : null;
        }

        if ($dateDebut) {
            $nbJours = now()->diffInDays($dateDebut) + 1;
            $caMoyenParJour = $nbJours > 0 ? $chiffreAffaires / $nbJours : 0;
        } else {
            $caMoyenParJour = 0;
        }

        $panierMoyen = $totalVentes > 0 ? $chiffreAffaires / $totalVentes : 0;

        // Vinyles vendus sur la période (requête optimisée avec jointure)
        $nbVinylesVendus = $startDate
            ? LigneVente::join('ventes', 'ventes.id', '=', 'ligne_ventes.vente_id')
                ->where('ventes.created_at', '>=', $startDate)
                ->sum('ligne_ventes.quantite') ?? 0
            : LigneVente::sum('quantite') ?? 0;

        $coutVinylesVendusPeriode = $nbVinylesVendus * $coutAchatVinyle;

        // Fonds vendus sur la période (requête optimisée avec jointure)
        $fondPeriodeStats = $startDate
            ? LigneVente::join('ventes', 'ventes.id', '=', 'ligne_ventes.vente_id')
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN fond = 'miroir' THEN quantite ELSE 0 END), 0) as miroir,
                    COALESCE(SUM(CASE WHEN fond = 'dore' THEN quantite ELSE 0 END), 0) as dore
                ")
                ->where('ventes.created_at', '>=', $startDate)
                ->first()
            : LigneVente::selectRaw("
                COALESCE(SUM(CASE WHEN fond = 'miroir' THEN quantite ELSE 0 END), 0) as miroir,
                COALESCE(SUM(CASE WHEN fond = 'dore' THEN quantite ELSE 0 END), 0) as dore
            ")->first();

        $nbMiroirsVendusPeriode = $fondPeriodeStats->miroir;
        $nbDoresVendusPeriode = $fondPeriodeStats->dore;
        $coutFondsVendusPeriode = ($nbMiroirsVendusPeriode + $nbDoresVendusPeriode) * $coutAchatFond;
        $margeBrute = $chiffreAffaires - ($coutVinylesVendusPeriode + $coutFondsVendusPeriode);

        // ======================================================
        // 8. AGRÉGATIONS POUR GRAPHIQUES
        // ======================================================

        $ventesParPeriode = DB::table('ventes')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$sqlGroupFormat}') as periode"),
                DB::raw('SUM(total) as ca')
            )
            ->when($startDate, function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate);
            })
            ->groupBy('periode')
            ->orderBy('periode')
            ->get();

        $paiements = DB::table('ventes')
            ->select(
                'mode_paiement',
                DB::raw('COUNT(*) as nb_ventes'),
                DB::raw('SUM(total) as total')
            )
            ->when($startDate, function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate);
            })
            ->groupBy('mode_paiement')
            ->get();

        // Top modèles vendus (requête optimisée)
        $topModelesVendus = LigneVente::select(
            'vinyles.modele',
            DB::raw('SUM(ligne_ventes.quantite) as total_vendus')
        )
            ->join('vinyles', 'vinyles.id', '=', 'ligne_ventes.vinyle_id')
            ->when($startDate, function ($q) use ($startDate) {
                $q->join('ventes', 'ventes.id', '=', 'ligne_ventes.vente_id')
                  ->where('ventes.created_at', '>=', $startDate);
            })
            ->groupBy('vinyles.id', 'vinyles.modele')
            ->orderByDesc('total_vendus')
            ->limit(30)
            ->get();

        // Stock bas et ruptures (requêtes agrégées)
        $stockStats = Vinyle::selectRaw("
            COUNT(CASE WHEN quantite > 0 AND quantite <= 3 THEN 1 END) as stock_bas,
            COUNT(CASE WHEN quantite <= 0 THEN 1 END) as ruptures
        ")->first();

        $stockBas = $stockStats->stock_bas;
        $rupturesStock = $stockStats->ruptures;

        // ======================================================
        // 9. RETOUR DES STATS
        // ======================================================

        return [
            'valeurStock'             => $valeurStock,
            'totalVinyles'            => $totalVinyles,
            'totalQuantiteVinyles'    => $totalQuantiteVinyles,
            'stockBas'                => $stockBas,
            'rupturesStock'           => $rupturesStock,
            'totalVentes'             => $totalVentes,
            'chiffreAffaires'         => $chiffreAffaires,
            'ventesParPeriode'        => $ventesParPeriode,
            'paiements'               => $paiements,
            'periode'                 => $periode,
            'periodeLabel'            => $periodeLabel,
            'grouping'                => $grouping,
            'nbVinylesVendus'         => $nbVinylesVendus,
            'caMoyenParJour'          => $caMoyenParJour,
            'panierMoyen'             => $panierMoyen,
            'topModelesVendus'        => $topModelesVendus,
            'margeBrute'              => $margeBrute,
            'quantiteVinylesStock'       => $quantiteVinylesStock,
            'quantiteVinylesVendus'      => $quantiteVinylesVendus,
            'quantiteVinylesAchetes'     => $quantiteVinylesAchetes,
            'valeurStockAchatVinyles'    => $valeurStockAchatVinyles,
            'coutAchatVinylesVendus'     => $coutAchatVinylesVendus,
            'investissementTotalVinyles' => $investissementTotalVinyles,
            'chiffreAffairesTotal'       => $chiffreAffairesTotal,
            'caTotalPossibleVinyles'     => $caTotalPossibleVinyles,
            'stockMiroir'                => $stockMiroir,
            'stockDore'                  => $stockDore,
            'quantiteFondsMiroirStock'   => $quantiteFondsMiroirStock,
            'quantiteFondsDoreStock'     => $quantiteFondsDoreStock,
            'quantiteFondsStockTotal'    => $quantiteFondsStockTotal,
            'valeurStockFonds'           => $valeurStockFonds,
            'quantiteFondsMiroirVendus'  => $quantiteFondsMiroirVendus,
            'quantiteFondsDoreVendus'    => $quantiteFondsDoreVendus,
            'quantiteFondsVendusTotal'   => $quantiteFondsVendusTotal,
            'quantiteFondsMiroirAchetes' => $quantiteFondsMiroirAchetes,
            'quantiteFondsDoreAchetes'   => $quantiteFondsDoreAchetes,
            'quantiteFondsAchetesTotal'  => $quantiteFondsAchetesTotal,
            'coutAchatFondsVendus'       => $coutAchatFondsVendus,
            'investissementTotalFonds'   => $investissementTotalFonds,
            'margeBruteTotale'           => $margeBruteTotale,
            'tauxMargeBruteTotale'       => $tauxMargeBruteTotale,
            'margeMoyenneParVinyle'      => $margeMoyenneParVinyle,
            'margePotentielleStock'      => $margePotentielleStock,
        ];
    }
}
