<?php

namespace App\Http\Controllers;

use App\Models\Vinyle;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Page d'accueil publique - Vinyle Hydrodécoupé
     */
    public function landing()
    {
        // Récupérer quelques vinyles en vedette pour la landing page
        $featured = Vinyle::where('quantite', '>', 0)
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        // Statistiques rapides
        $stats = [
            'total' => Vinyle::where('quantite', '>', 0)->count(),
            'recent' => Vinyle::where('quantite', '>', 0)
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ];

        return view('landing', compact('featured', 'stats'));
    }

    /**
     * Page À propos
     */
    public function about()
    {
        return view('about');
    }

    /**
     * Page Contact
     */
    public function contact()
    {
        return view('contact');
    }
}