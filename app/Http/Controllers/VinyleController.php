<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVinyleRequest;
use App\Http\Requests\UpdateVinyleRequest;
use App\Models\Vinyle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VinyleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $vinyles = Vinyle::with('media')
            ->latest()
            ->paginate(10);

        return view('vinyles.index', compact('vinyles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('vinyles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVinyleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Créer le vinyle
        $vinyle = Vinyle::create($validated);
        
        // Gérer les images si présentes
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $vinyle->addMedia($image)
                    ->toMediaCollection('photo');
            }
        }

        return redirect()
            ->route('vinyles.index')
            ->with('success', 'Vinyle créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Vinyle $vinyle): View
    {
        $vinyle->load(['media', 'ventes', 'orderItems']);
        
        return view('vinyles.show', compact('vinyle'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vinyle $vinyle): View
    {
        $vinyle->load('media');
        
        return view('vinyles.edit', compact('vinyle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVinyleRequest $request, Vinyle $vinyle): RedirectResponse
    {
        $validated = $request->validated();
        
        // Mettre à jour le vinyle
        $vinyle->update($validated);
        
        // Gérer les nouvelles images
        if ($request->hasFile('images')) {
            // Limite de 3 images totales
            $currentCount = $vinyle->getMedia('photo')->count();
            $maxNewImages = max(0, 3 - $currentCount);
            
            $images = array_slice($request->file('images'), 0, $maxNewImages);
            
            foreach ($images as $image) {
                $vinyle->addMedia($image)
                    ->toMediaCollection('photo');
            }
        }
        
        // Supprimer des images si demandé
        if ($request->has('delete_images')) {
            foreach ($request->input('delete_images') as $mediaId) {
                $media = $vinyle->getMedia('photo')->firstWhere('id', $mediaId);
                if ($media) {
                    $media->delete();
                }
            }
        }

        return redirect()
            ->route('vinyles.index')
            ->with('success', 'Vinyle mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vinyle $vinyle): RedirectResponse
    {
        // Vérifier si le vinyle a des ventes ou commandes associées
        if ($vinyle->ventes()->exists() || $vinyle->orderItems()->exists()) {
            return redirect()
                ->route('vinyles.index')
                ->with('error', 'Impossible de supprimer ce vinyle car il est associé à des ventes ou commandes.');
        }
        
        $vinyle->delete();

        return redirect()
            ->route('vinyles.index')
            ->with('success', 'Vinyle supprimé avec succès.');
    }
    
    /**
     * Affiche le kiosque public (catalogue)
     */
    public function kiosque(Request $request): View
    {
        $query = Vinyle::with('media')
            ->where('quantite', '>', 0);
        
        // Filtres
        if ($request->filled('artiste')) {
            $query->where('artiste', 'like', '%' . $request->artiste . '%');
        }
        
        if ($request->filled('genre')) {
            $query->where('genre', $request->genre);
        }
        
        if ($request->filled('style')) {
            $query->where('style', $request->style);
        }
        
        // Tri
        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'price_asc' => $query->orderBy('prix', 'asc'),
            'price_desc' => $query->orderBy('prix', 'desc'),
            'alpha' => $query->orderBy('artiste', 'asc'),
            default => $query->latest(),
        };
        
        $vinyles = $query->paginate(12)->withQueryString();
        
        // Liste des genres et styles pour les filtres
        $genres = Vinyle::distinct()->pluck('genre')->filter()->values();
        $styles = Vinyle::distinct()->pluck('style')->filter()->values();
        
        return view('kiosque.kiosque', compact('vinyles', 'genres', 'styles'));
    }

    /**
     * Recherche avancée des vinyles (admin/employé)
     */
    public function search(Request $request): View
    {
        $query = Vinyle::with('media');

        // Recherche texte (artiste, album, référence)
        if ($request->filled('q')) {
            $searchTerm = $request->input('q');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('artiste', 'like', '%' . $searchTerm . '%')
                  ->orWhere('modele', 'like', '%' . $searchTerm . '%')
                  ->orWhere('reference', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filtre par genre
        if ($request->filled('genre')) {
            $query->where('genre', $request->input('genre'));
        }

        // Filtre par style
        if ($request->filled('style')) {
            $query->where('style', $request->input('style'));
        }

        // Filtre par prix min
        if ($request->filled('prix_min')) {
            $query->where('prix', '>=', $request->input('prix_min'));
        }

        // Filtre par prix max
        if ($request->filled('prix_max')) {
            $query->where('prix', '<=', $request->input('prix_max'));
        }

        // Filtre par année (basé sur created_at)
        if ($request->filled('annee')) {
            $query->whereYear('created_at', $request->input('annee'));
        }

        // Tri
        $sort = $request->input('sort', 'date_ajout_desc');
        match ($sort) {
            'prix_asc' => $query->orderBy('prix', 'asc'),
            'prix_desc' => $query->orderBy('prix', 'desc'),
            'artiste' => $query->orderBy('artiste', 'asc'),
            'date_ajout_desc' => $query->orderBy('created_at', 'desc'),
            'date_ajout_asc' => $query->orderBy('created_at', 'asc'),
            default => $query->latest(),
        };

        $vinyles = $query->paginate(12)->withQueryString();

        // Liste des genres et styles pour les filtres
        $genres = Vinyle::distinct()->pluck('genre')->filter()->values();
        $styles = Vinyle::distinct()->pluck('style')->filter()->values();

        // Liste des années disponibles
        $annees = Vinyle::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->values();

        return view('vinyles.search', compact('vinyles', 'genres', 'styles', 'annees'));
    }
}
