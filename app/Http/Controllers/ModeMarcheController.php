<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vente;
use App\Models\Vinyle;
use App\Services\StockMovementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ModeMarcheController extends Controller
{
    /**
     * Interface principale du mode marché
     * Affichage mobile-optimisé du catalogue avec panier
     */
    public function index()
    {
        $vinyles = Vinyle::where('quantite', '>', 0)
            ->orderBy('modele')
            ->get()
            ->map(function ($vinyle) {
                return [
                    'id' => $vinyle->id,
                    'nom' => $vinyle->modele,
                    'artiste_principale' => $vinyle->artiste ?? 'Artiste inconnu',
                    'prix' => $vinyle->prix,
                    'quantite' => $vinyle->quantite,
                    'image_url' => $vinyle->image_urls[0] ?? null,
                ];
            });

        return view('admin.marche.index', compact('vinyles'));
    }

    /**
     * Valider une vente marché - création commande rapide
     * POST /admin/marche/store
     */
    public function store(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.vinyle_id' => 'required|exists:vinyles,id',
            'items.*.quantite' => 'required|integer|min:1',
            'mode_paiement' => 'required|in:cash,cb_terminal,cheque,virement',
            'affichage_client' => 'nullable|string|max:100',
            'notes_vendeur' => 'nullable|string|max:500',
            'reduction' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        try {
            return DB::transaction(function () use ($data) {
                // Vérifier le stock pour chaque item
                $items = [];
                $total = 0;

                foreach ($data['items'] as $itemData) {
                    $vinyle = Vinyle::lockForUpdate()->findOrFail($itemData['vinyle_id']);

                    if ($vinyle->quantite < $itemData['quantite']) {
                        throw new \Exception(
                            "Stock insuffisant pour '{$vinyle->nom}' (dispo: {$vinyle->quantite}, demandé: {$itemData['quantite']})"
                        );
                    }

                    $items[] = [
                        'vinyle' => $vinyle,
                        'quantite' => $itemData['quantite'],
                        'prix_unitaire' => $vinyle->prix,
                        'total' => $vinyle->prix * $itemData['quantite'],
                    ];

                    $total += $vinyle->prix * $itemData['quantite'];
                }

                // Appliquer réduction si présente
                $reduction = $data['reduction'] ?? 0;
                $totalFinal = max(0, $total - $reduction);

                // Créer la commande mode marché
                $order = Order::create([
                    'numero_commande' => Order::generateNumero(),
                    'user_id' => auth()->id(), // L'admin/employé qui vend
                    'statut' => 'payee', // Directement payée
                    'total' => $totalFinal,
                    'source' => 'marche',
                    'mode_paiement_marche' => $data['mode_paiement'],
                    'notes_vendeur' => $data['notes_vendeur'] ?? null,
                    'affichage_client' => $data['affichage_client'] ?? null,
                    // Champs obligatoires mais vides pour le marché
                    'nom' => 'Vente sur place',
                    'prenom' => '',
                    'email' => auth()->user()->email ?? 'marche@local',
                    'telephone' => '',
                    'adresse' => 'Vente marché',
                    'code_postal' => '',
                    'ville' => '',
                    'shipping_nom' => $data['affichage_client'] ?? 'Client marché',
                    'shipping_prenom' => '',
                    'shipping_email' => '',
                    'shipping_telephone' => '',
                    'shipping_adresse' => 'Vente sur place',
                    'billing_nom' => 'Vente sur place',
                    'billing_prenom' => '',
                    'billing_email' => '',
                    'billing_telephone' => '',
                    'billing_adresse' => 'Vente sur place',
                ]);

                // Créer les items et décrémenter le stock avec mouvement traçé
                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'vinyle_id' => $item['vinyle']->id,
                        'titre_vinyle' => $item['vinyle']->nom,
                        'artiste_vinyle' => $item['vinyle']->artiste_principale?->nom ?? 'Artiste inconnu',
                        'reference_vinyle' => $item['vinyle']->modele ?: $item['vinyle']->nom,
                        'quantite' => $item['quantite'],
                        'prix_unitaire' => $item['prix_unitaire'],
                        'total' => $item['total'],
                    ]);

                    // Décrémentation via StockMovementService
                    StockMovementService::sortieStock(
                        produitType: 'vinyle',
                        produitId: $item['vinyle']->id,
                        quantite: $item['quantite'],
                        reference: $order->numero_commande,
                        notes: "Vente marché - {$item['vinyle']->nom}"
                    );
                }

                return response()->json([
                    'success' => true,
                    'order_id' => $order->id,
                    'numero_commande' => $order->numero_commande,
                    'total' => $totalFinal,
                    'items_count' => count($items),
                ]);
            });

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier le stock disponible en temps réel
     * GET /admin/marche/check-stock/{vinyleId}
     */
    public function checkStock($vinyleId)
    {
        $vinyle = Vinyle::select('id', 'nom', 'quantite', 'prix')->find($vinyleId);

        if (!$vinyle) {
            return response()->json(['error' => 'Vinyle non trouvé'], 404);
        }

        return response()->json([
            'id' => $vinyle->id,
            'nom' => $vinyle->nom,
            'stock' => $vinyle->quantite,
            'prix' => $vinyle->prix,
            'available' => $vinyle->quantite > 0,
        ]);
    }

    /**
     * Liste des ventes du jour
     * GET /admin/marche/ventes-jour?view=json
     */
    public function ventesJour(Request $request)
    {
        // Récupérer la date demandée (défaut: aujourd'hui)
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

        // Navigation: dates précédente et suivante avec ventes
        $datePrecedenteRaw = Order::where('source', 'marche')
            ->whereDate('created_at', '<', $dateString)
            ->select(DB::raw('DATE(created_at) as date'))
            ->distinct()
            ->orderBy('date', 'desc')
            ->first()?->date;
        
        $dateSuivanteRaw = Order::where('source', 'marche')
            ->whereDate('created_at', '>', $dateString)
            ->select(DB::raw('DATE(created_at) as date'))
            ->distinct()
            ->orderBy('date', 'asc')
            ->first()?->date;

        // Convertir en objets Carbon pour la vue
        $datePrecedente = $datePrecedenteRaw ? Carbon::parse($datePrecedenteRaw) : null;
        $dateSuivante = $dateSuivanteRaw ? Carbon::parse($dateSuivanteRaw) : null;

        // Si requête JSON (API/tests), retourner JSON
        if ($request->wantsJson() || $request->query('view') === 'json') {
            $ventesArray = $ventes->map(function ($vente) {
                return [
                    'id' => $vente->id,
                    'numero_commande' => $vente->numero_commande ?? 'N/A',
                    'total' => $vente->total,
                    'mode_paiement' => $vente->mode_paiement_marche ?? 'cash',
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
                'date_precedente' => $datePrecedente,
                'date_suivante' => $dateSuivante,
            ]);
        }

        return view('marche.ventes-jour', compact(
            'ventes',
            'dateSelectionnee',
            'totalJour',
            'datePrecedente',
            'dateSuivante'
        ));
    }

    /**
     * Annuler une vente marché (restock)
     * POST /admin/marche/{order}/cancel
     */
    public function cancel(Order $order)
    {
        if ($order->source !== 'marche') {
            return response()->json(['error' => 'Seules les ventes marché peuvent être annulées ici'], 403);
        }

        if ($order->statut === 'annulée') {
            return response()->json(['error' => 'Cette vente est déjà annulée'], 400);
        }

        // Vérifier la limite de temps (24h)
        if ($order->created_at->diffInHours(now()) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'annuler une vente de plus de 24h'
            ], 422);
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
     * GET /admin/marche/export?format=csv
     */
    public function export(Request $request)
    {
        $format = $request->input('format', 'csv');
        
        if ($format !== 'csv') {
            return response()->json(['error' => 'Format non supporté. Utilisez ?format=csv'], 400);
        }

        $ventes = Order::where('source', 'marche')
            ->whereDate('created_at', today())
            ->with('items.vinyle')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'ventes_marche_' . now()->format('Y-m-d') . '.csv';

        // Générer le CSV
        $output = fopen('php://temp', 'r+');
        
        // BOM UTF-8 pour Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Headers CSV
        fputcsv($output, [
            'N° Commande',
            'Heure',
            'Client',
            'Articles',
            'Mode Paiement',
            'Total (€)'
        ], ';');

        foreach ($ventes as $vente) {
            fputcsv($output, [
                $vente->numero_commande,
                $vente->created_at->format('H:i'),
                $vente->affichage_client ?? 'Anonyme',
                $vente->items->count(),
                $vente->mode_paiement_marche ?? 'N/A',
                number_format($vente->total, 2, ',', ' ')
            ], ';');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
