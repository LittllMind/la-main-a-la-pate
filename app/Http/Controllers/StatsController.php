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
        // 3. STATS CATALOGUE & STOCK (indépendantes de la période - avec cache)
        // ======================================================

        // --- VINYLES ---

        // Nombre de modèles au catalogue
        $totalVinyles = Vinyle::count();

        // Quantité totale de vinyles en stock
        $totalQuantiteVinyles = Vinyle::sum('quantite') ?? 0;
        $quantiteVinylesStock = $totalQuantiteVinyles;

        // Valeur d'achat du stock vinyles
        $valeurStockAchatVinyles = $quantiteVinylesStock * $coutAchatVinyle;

        // Valeur du stock au prix catalogue
        $valeurStock = Vinyle::sum(DB::raw('prix * quantite')) ?? 0;

        // CA total historique
        $chiffreAffairesTotal = Vente::sum('total') ?? 0;

        // CA potentiel du stock
        $caStockPotentielVinyles = $valeurStock;
        $caTotalPossibleVinyles = $chiffreAffairesTotal + $caStockPotentielVinyles;

        // --- FONDS ---
        $stockMiroir = Fond::where('type', 'miroir')->sum('quantite') ?? 0;
        $stockDore   = Fond::where('type', 'dore')->sum('quantite') ?? 0;

        $quantiteFondsMiroirStock = $stockMiroir;
        $quantiteFondsDoreStock   = $stockDore;
        $quantiteFondsStockTotal  = $quantiteFondsMiroirStock + $quantiteFondsDoreStock;
        $valeurStockFonds = $quantiteFondsStockTotal * $coutAchatFond;

        // ======================================================
        // 4. VINYLES – HISTORIQUE
        // ======================================================

        // Quantité totale de vinyles vendus
        $quantiteVinylesVendus = LigneVente::sum('quantite') ?? 0;
        $quantiteVinylesAchetes = $quantiteVinylesStock + $quantiteVinylesVendus;
        $coutAchatVinylesVendus = $quantiteVinylesVendus * $coutAchatVinyle;
        $investissementTotalVinyles = $quantiteVinylesAchetes * $coutAchatVinyle;

        // ======================================================
        // 5. FONDS – HISTORIQUE
        // ======================================================

        $quantiteFondsMiroirVendus = LigneVente::where('fond', 'miroir')->sum('quantite');
        $quantiteFondsDoreVendus   = LigneVente::where('fond', 'dore')->sum('quantite');
        $quantiteFondsVendusTotal  = $quantiteFondsMiroirVendus + $quantiteFondsDoreVendus;

        $quantiteFondsMiroirAchetes = $quantiteFondsMiroirStock + $quantiteFondsMiroirVendus;
        $quantiteFondsDoreAchetes   = $quantiteFondsDoreStock   + $quantiteFondsDoreVendus;
        $quantiteFondsAchetesTotal  = $quantiteFondsMiroirAchetes + $quantiteFondsDoreAchetes;

        $coutAchatFondsVendus = $quantiteFondsVendusTotal * $coutAchatFond;
        $investissementTotalFonds = $quantiteFondsAchetesTotal * $coutAchatFond;

        // ======================================================
        // 6. MARGES GLOBALES
        // ======================================================

        $coutTotalHistorique    = $coutAchatVinylesVendus + $coutAchatFondsVendus;
        $margeBruteTotale       = $chiffreAffairesTotal - $coutTotalHistorique;
        $tauxMargeBruteTotale   = $chiffreAffairesTotal > 0
            ? ($margeBruteTotale / $chiffreAffairesTotal) * 100
            : 0;

        $margeMoyenneParVinyle  = $quantiteVinylesVendus > 0
            ? $margeBruteTotale / $quantiteVinylesVendus
            : 0;

        $margePotentielleStock = $valeurStock - $valeurStockAchatVinyles;

        // ======================================================
        // 7. STATS VENTES SUR LA PÉRIODE
        // ======================================================

        $ventesPeriodeQuery = Vente::query();
        if ($startDate) {
            $ventesPeriodeQuery->where('created_at', '>=', $startDate);
        }
        $ventesPeriode = $ventesPeriodeQuery->get();

        $totalVentes     = $ventesPeriode->count();
        $chiffreAffaires = $ventesPeriode->sum('total');

        // CA moyen par jour
        if ($startDate) {
            $dateDebut = $startDate;
        } else {
            $minCreated = Vente::min('created_at');
            $dateDebut  = $minCreated ? \Carbon\Carbon::parse($minCreated) : null;
        }

        if ($dateDebut) {
            $nbJours = now()->diffInDays($dateDebut) + 1;
            $caMoyenParJour = $nbJours > 0 ? $chiffreAffaires / $nbJours : 0;
        } else {
            $caMoyenParJour = 0;
        }

        $panierMoyen = $totalVentes > 0 ? $chiffreAffaires / $totalVentes : 0;

        // Vinyles vendus sur la période
        $nbVinylesVendus = LigneVente::whereHas('vente', function ($q) use ($startDate) {
            if ($startDate) {
                $q->where('created_at', '>=', $startDate);
            }
        })->sum('quantite') ?? 0;

        $coutVinylesVendusPeriode = $nbVinylesVendus * $coutAchatVinyle;

        // Fonds vendus sur la période
        $nbMiroirsVendusPeriode = LigneVente::where('fond', 'miroir')
            ->whereHas('vente', function ($q) use ($startDate) {
                if ($startDate) {
                    $q->where('created_at', '>=', $startDate);
                }
            })->sum('quantite');

        $nbDoresVendusPeriode = LigneVente::where('fond', 'dore')
            ->whereHas('vente', function ($q) use ($startDate) {
                if ($startDate) {
                    $q->where('created_at', '>=', $startDate);
                }
            })->sum('quantite');

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

        $topModelesVendus = LigneVente::select(
            'vinyles.modele',
            DB::raw('SUM(ligne_ventes.quantite) as total_vendus')
        )
            ->join('vinyles', 'vinyles.id', '=', 'ligne_ventes.vinyle_id')
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereHas('vente', function ($sub) use ($startDate) {
                    $sub->where('created_at', '>=', $startDate);
                });
            })
            ->groupBy('vinyles.id', 'vinyles.modele')
            ->orderByDesc('total_vendus')
            ->limit(30)
            ->get();

        $stockBas = Vinyle::where('quantite', '>', 0)
            ->where('quantite', '<=', 3)
            ->count();

        $rupturesStock = Vinyle::where('quantite', '<=', 0)->count();

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
