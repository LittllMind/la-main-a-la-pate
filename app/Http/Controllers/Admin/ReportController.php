<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Vinyle;
use App\Models\Fond;
use App\Models\MouvementStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * T13.1-SR : Rapport de stock global
     */
    public function stock()
    {
        // ===== VINYLES =====
        $vinyles = Vinyle::all();
        $totalVinylesValue = $vinyles->sum(fn($v) => $v->quantite * $v->prix);
        $totalVinylesQuantity = $vinyles->sum('quantite');
        $vinylesCount = $vinyles->count();
        
        // Répartition par genre
        $vinylesByGenre = $vinyles->groupBy('genre')->map(fn($g) => [
            'count' => $g->count(),
            'quantity' => $g->sum('quantite'),
            'value' => $g->sum(fn($v) => $v->quantite * $v->prix),
        ]);
        
        // Alertes stock bas
        $lowStockVinyles = $vinyles->filter(fn($v) => $v->quantite > 0 && $v->quantite <= ($v->seuil_alerte ?? 3));
        $outOfStockVinyles = $vinyles->filter(fn($v) => $v->quantite <= 0);
        
        // ===== FONDS =====
        $fonds = Fond::all();
        $totalFondsValue = $fonds->sum(fn($f) => $f->quantite * $f->prix_vente);
        $totalFondsQuantity = $fonds->sum('quantite');
        
        // Répartition par type
        $fondsByType = $fonds->groupBy('type')->map(fn($g) => [
            'count' => $g->count(),
            'quantity' => $g->sum('quantite'),
            'value' => $g->sum(fn($f) => $f->quantite * $f->prix_vente),
        ]);
        
        // ===== TOTAL =====
        $totalStockValue = $totalVinylesValue + $totalFondsValue;
        $totalQuantity = $totalVinylesQuantity + $totalFondsQuantity;
        
        return view('admin.reports.stock', [
            'vinylesCount' => $vinylesCount,
            'totalVinylesQuantity' => $totalVinylesQuantity,
            'totalVinylesValue' => $totalVinylesValue,
            'vinylesByGenre' => $vinylesByGenre,
            'lowStockVinyles' => $lowStockVinyles,
            'outOfStockVinyles' => $outOfStockVinyles,
            'totalFondsQuantity' => $totalFondsQuantity,
            'totalFondsValue' => $totalFondsValue,
            'fondsByType' => $fondsByType,
            'totalStockValue' => $totalStockValue,
            'totalQuantity' => $totalQuantity,
        ]);
    }

    /**
     * T13.1-AR : Rapport par artiste
     */
    public function artists(Request $request)
    {
        $letter = $request->get('letter');
        
        // Base query
        $query = Vinyle::with(['ventes'])
            ->select('artiste')
            ->selectRaw('COUNT(*) as titres_count')
            ->selectRaw('SUM(quantite) as stock_quantity')
            ->selectRaw('SUM(quantite * prix) as stock_value')
            ->groupBy('artiste');
        
        // Filtrer par lettre
        if ($letter) {
            $query->where('artiste', 'LIKE', $letter . '%');
        }
        
        // Trier par valeur décroissante
        $artists = $query->orderByDesc('stock_value')->get();
        
        // Ajouter les infos de ventes
        $artists = $artists->map(function ($artist) {
            $vinyles = Vinyle::where('artiste', $artist->artiste)->get();
            
            // Calcul des ventes
            $totalVendu = 0;
            $quantiteVendue = 0;
            foreach ($vinyles as $vinyle) {
                $ventes = $vinyle->ventes()->sum('quantite');
                $quantiteVendue += $ventes;
                $totalVendu += $ventes * $vinyle->prix;
            }
            
            $artist->quantite_vendue = $quantiteVendue;
            $artist->ca_vendu = $totalVendu;
            $artist->has_out_of_stock = $vinyles->contains(fn($v) => $v->quantite <= 0);
            $artist->has_low_stock = $vinyles->contains(fn($v) => $v->quantite > 0 && $v->quantite <= ($v->seuil_alerte ?? 3));
            
            return $artist;
        });
        
        return view('admin.reports.artists', [
            'artists' => $artists,
            'letter' => $letter,
            'alphabet' => range('A', 'Z'),
        ]);
    }

    /**
     * Exporte l'inventaire des vinyles en PDF
     */
    public function exportVinylesInventory()
    {
        $vinyles = Vinyle::orderBy('artiste')->orderBy('modele')->get();
        $totalValue = $vinyles->sum(fn($v) => $v->quantite * $v->prix);
        
        $pdf = $this->generateInventoryPdf(
            'VINYLES',
            $vinyles,
            $totalValue,
            fn($v) => [
                'REF: ' . $v->reference,
                $v->artiste . ' - ' . $v->modele,
                $v->genre . ' / ' . $v->style,
                'Stock: ' . $v->quantite . ' × ' . number_format($v->prix, 2, ',', ' ') . ' €'
            ]
        );
        
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="inventaire-vinyles-' . date('Y-m-d') . '.pdf"'
        ]);
    }

    /**
     * Exporte l'inventaire des fonds en PDF
     */
    public function exportFondsInventory()
    {
        $fonds = Fond::orderBy('type')->orderBy('prix_vente')->get();
        $totalValue = $fonds->sum(fn($f) => $f->quantite * $f->prix_vente);
        
        $pdf = $this->generateInventoryPdf(
            'FONDS',
            $fonds,
            $totalValue,
            fn($f) => [
                $f->type,
                'Tarif: ' . number_format($f->prix_vente, 2, ',', ' ') . ' €',
                'Stock: ' . $f->quantite . ' unités',
                'Valeur: ' . number_format($f->quantite * $f->prix_vente, 2, ',', ' ') . ' €'
            ]
        );
        
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="inventaire-fonds-' . date('Y-m-d') . '.pdf"'
        ]);
    }

    /**
     * Génère un formulaire de sélection de mois pour le bilan mensuel
     */
    public function monthlyReportForm()
    {
        return view('admin.reports.monthly-form');
    }

    /**
     * Génère le bilan mensuel PDF
     */
    public function generateMonthlyReport(Request $request)
    {
        try {
            $request->validate([
                'month' => 'required|date_format:Y-m',
            ]);

            $month = $request->input('month');
            $startDate = Carbon::parse($month . '-01')->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            // Ventes du mois
            $monthlyOrders = Order::where('statut', 'livree')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            
            $totalSales = $monthlyOrders->sum('total');
            $ordersCount = $monthlyOrders->count();

            // Top produits vendus
            $topProducts = $this->getTopProductsSold($startDate, $endDate);

            // Mouvements de stock du mois
            $stockMovements = MouvementStock::whereBetween('created_at', [$startDate, $endDate])
                ->get();
            
            $entriesCount = $stockMovements->where('type', 'entree')->count();
            $exitsCount = $stockMovements->where('type', 'sortie')->count();

            // Stock actuel
            $vinylesStock = Vinyle::sum('quantite');
            $fondsStock = Fond::sum('quantite');
            
            // Calcul stock value
            $vinylesValue = Vinyle::selectRaw('COALESCE(SUM(quantite * prix), 0) as value')->first()->value ?? 0;
            $fondsValue = Fond::selectRaw('COALESCE(SUM(quantite * prix_vente), 0) as value')->first()->value ?? 0;
            $stockValue = $vinylesValue + $fondsValue;

            // Générer le PDF
            $pdf = $this->generateMonthlyReportPdf([
                'month' => $month,
                'monthLabel' => $startDate->translatedFormat('F Y'),
                'totalSales' => $totalSales,
                'ordersCount' => $ordersCount,
                'topProducts' => $topProducts,
                'entriesCount' => $entriesCount,
                'exitsCount' => $exitsCount,
                'vinylesStock' => $vinylesStock,
                'fondsStock' => $fondsStock,
                'stockValue' => $stockValue,
                'generatedAt' => now()->format('d/m/Y H:i'),
            ]);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="bilan-mensuel-' . $month . '.pdf"'
            ]);
        } catch (\Exception $e) {
            \Log::error('Monthly report error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retourne les produits les plus vendus
     */
    private function getTopProductsSold($startDate, $endDate)
    {
        try {
            return \DB::table('lignes_ventes')
                ->join('vinyles', 'lignes_ventes.vinyle_id', '=', 'vinyles.id')
                ->join('ventes', 'lignes_ventes.vente_id', '=', 'ventes.id')
                ->whereBetween('ventes.created_at', [$startDate, $endDate])
                ->select('vinyles.artiste', 'vinyles.modele', 'vinyles.prix', \DB::raw('SUM(lignes_ventes.quantite) as total_vendu'))
                ->groupBy('vinyles.id', 'vinyles.artiste', 'vinyles.modele', 'vinyles.prix')
                ->orderByRaw('total_vendu DESC')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            // Si la table ligne_ventes n'existe pas, retourner collection vide
            return collect();
        }
    }

    /**
     * Génère le PDF du bilan mensuel
     */
    private function generateMonthlyReportPdf($data)
    {
        $content = "";
        $content .= "BILAN MENSUEL - " . strtoupper($data['monthLabel']) . "\n";
        $content .= str_repeat("=", 50) . "\n\n";
        
        $content .= "VENTES DU MOIS\n";
        $content .= "-" . str_repeat("-", 40) . "\n";
        $content .= "Nombre de commandes: " . $data['ordersCount'] . "\n";
        $content .= "Total des ventes: " . number_format($data['totalSales'], 2, ',', ' ') . " €\n\n";
        
        $content .= "TOP PRODUITS VENDUS\n";
        $content .= "-" . str_repeat("-", 40) . "\n";
        foreach ($data['topProducts'] as $i => $product) {
            $content .= ($i + 1) . ". " . $product->artiste . " - " . $product->modele;
            $content .= " (" . $product->total_vendu . " vendus)\n";
        }
        $content .= "\n";
        
        $content .= "MOUVEMENTS DE STOCK\n";
        $content .= "-" . str_repeat("-", 40) . "\n";
        $content .= "Entrées: " . $data['entriesCount'] . "\n";
        $content .= "Sorties: " . $data['exitsCount'] . "\n\n";
        
        $content .= "INVENTAIRE ACTUEL\n";
        $content .= "-" . str_repeat("-", 40) . "\n";
        $content .= "Vinyles en stock: " . $data['vinylesStock'] . " unités\n";
        $content .= "Fonds en stock: " . $data['fondsStock'] . " unités\n";
        $content .= "Valeur du stock: " . number_format($data['stockValue'], 2, ',', ' ') . " €\n\n";
        
        $content .= "Généré le " . $data['generatedAt'] . "\n";

        return $this->generatePdfFromText("BILAN MENSUEL - " . $data['monthLabel'], $content);
    }

    /**
     * Génère un PDF d'inventaire simplifié
     */
    private function generateInventoryPdf($title, $items, $totalValue, $formatter)
    {
        $content = "INVENTAIRE $title\n";
        $content .= str_repeat("=", 50) . "\n";
        $content .= "Généré le " . date('d/m/Y H:i') . "\n\n";
        $content .= "VALEUR TOTALE: " . number_format($totalValue, 2, ',', ' ') . " €\n\n";
        $content .= str_repeat("-", 50) . "\n\n";

        foreach ($items as $item) {
            $lines = $formatter($item);
            foreach ($lines as $line) {
                $content .= $line . "\n";
            }
            $content .= "\n";
        }

        return $this->generatePdfFromText("INVENTAIRE $title", $content);
    }

    /**
     * Génère un PDF minimal à partir de texte
     */
    private function generatePdfFromText($title, $content)
    {
        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
        $pdf .= "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
        
        $textStream = $this->escapePdfText($content);
        $stream = "<< /Length " . strlen($textStream) . " >>\nstream\n$textStream\nendstream";
        
        $pdf .= "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >> endobj\n";
        $pdf .= "4 0 obj $stream endobj\n";
        $pdf .= "xref\n0 5\n0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "1 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "2 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "3 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "4 0 obj"));
        $pdf .= "trailer << /Size 5 /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . strpos($pdf, "xref") . "\n%%EOF";

        return $pdf;
    }

    /**
     * Échappe le texte pour le PDF
     */
    private function escapePdfText($text)
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        return "BT /F1 12 Tf 50 750 Td ($text) Tj ET";
    }
}