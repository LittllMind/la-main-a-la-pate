<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Vinyle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API Controller pour Mode Marché
 * Routes: /api/marche/*
 */
class ModeMarcheApiController extends Controller
{
    /**
     * Liste des ventes du jour - API JSON
     * GET /api/marche/ventes-jour
     */
    public function ventesJour(Request $request)
    {
        $dateParam = $request->query('date');
        $dateSelectionnee = $dateParam ? Carbon::parse($dateParam) : now();
        $dateString = $dateSelectionnee->toDateString();

        // Récupérer les ventes du modèle Order avec source='marche' pour cette date
        $ventes = Order::with('items.vinyle')
            ->where('source', 'marche')
            ->whereDate('created_at', $dateString)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculer le total du jour
        $totalJour = $ventes->sum('total');

        // Mapping des modes de paiement vers labels lisibles
        $modePaiementMap = [
            'cash' => 'espèces',
            'cb_terminal' => 'carte',
            'cheque' => 'chèque',
            'virement' => 'virement',
        ];

        $ventesArray = $ventes->map(function ($vente) use ($modePaiementMap) {
            $modeTech = $vente->mode_paiement_marche ?? 'cash';
            return [
                'id' => $vente->id,
                'numero_commande' => $vente->numero_commande ?? 'N/A',
                'total' => $vente->total,
                'mode_paiement' => $modePaiementMap[$modeTech] ?? $modeTech,
                'created_at' => $vente->created_at->toISOString(),
                'items_count' => $vente->items->count(),
                'client' => $vente->affichage_client ?? 'Anonyme',
            ];
        });

        return response()->json([
            'ventes' => $ventesArray,
            'total_jour' => (float) $totalJour,
            'nb_ventes' => $ventes->count(),
            'date' => $dateString,
        ]);
    }

    /**
     * Annuler une vente marché (restock)
     * POST /api/marche/{order}/cancel
     */
    public function cancel(Order $order)
    {
        if ($order->source !== 'marche') {
            return response()->json(
                ['error' => 'Seules les ventes marché peuvent être annulées ici'],
                403
            );
        }

        if ($order->statut === 'annulee') {
            return response()->json(
                ['error' => 'Cette vente est déjà annulée'],
                400
            );
        }

        try {
            DB::transaction(function () use ($order) {
                // Restocker les vinyles
                foreach ($order->items as $item) {
                    $vinyle = Vinyle::find($item->vinyle_id);
                    if ($vinyle) {
                        $vinyle->quantite += $item->quantite;
                        $vinyle->save();
                    }
                }

                // Marquer comme annulée
                $order->statut = 'annulee';
                $order->annulee_at = now();
                $order->save();
            });

            return response()->json([
                'success' => true,
                'message' => 'Vente annulée et stock restauré'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export des ventes du jour au format CSV
     * GET /api/marche/export?format=csv
     */
    public function export(Request $request)
    {
        $format = $request->input('format', 'csv');

        if ($format !== 'csv') {
            return response()->json(
                ['error' => 'Format non supporté. Utilisez ?format=csv'],
                400
            );
        }

        $ventes = Order::where('source', 'marche')
            ->whereDate('created_at', today())
            ->with('items.vinyle')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'ventes_marche_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($ventes) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 pour Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers CSV
            fputcsv($handle, [
                'N° Commande',
                'Heure',
                'Client',
                'Articles',
                'Mode Paiement',
                'Total (€)'
            ], ';');

            foreach ($ventes as $vente) {
                fputcsv($handle, [
                    $vente->numero_commande,
                    $vente->created_at->format('H:i'),
                    $vente->affichage_client ?? 'Anonyme',
                    $vente->items->count(),
                    $vente->mode_paiement_marche ?? 'N/A',
                    number_format($vente->total, 2, ',', ' ')
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
