<?php

namespace App\Http\Controllers;

use App\Models\Vinyle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VinyleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('kiosque');
    }

    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $filter = $request->get('filter', null);

        $vinyles = Vinyle::query()
            ->with(['media']) // eager load photos
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('artiste', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('modele', 'like', "%{$search}%")
                        ->orWhere('genre', 'like', "%{$search}%");
                });
            })
            ->when($filter === 'stock_bas', function ($query) {
                $query->where('quantite', '>', 0)
                    ->whereColumn('quantite', '<=', 'seuil_alerte');
            })
            ->when($filter === 'rupture', function ($query) {
                $query->where('quantite', '<=', 0);
            })
            ->orderBy('artiste')
            ->orderBy('modele')
            ->withCount(['ventes'])
            ->paginate(25)
            ->appends($request->only('search', 'filter'));

        return view('vinyles.index', compact('vinyles', 'search', 'filter'));
    }

    public function create()
    {
        return view('vinyles.form', ['vinyle' => new Vinyle()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'required|string|max:50|unique:vinyles',
            'artiste' => 'required|string|max:255',
            'modele' => 'required|string|max:255',
            'genre' => 'nullable|string|max:100',
            'style' => 'nullable|string|max:100',
            'prix' => 'required|numeric|min:0',
            'quantite' => 'required|integer|min:0',
            'seuil_alerte' => 'required|integer|min:1',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // STORE
        $vinyle = Vinyle::create($validated);

        // Upload des photos (3 max)
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $photo) {
                $vinyle->addMedia($photo)
                    ->withCustomProperties(['order' => $index])
                    ->toMediaCollection('photo');
            }
        }

        return redirect()->route('vinyles.index')
            ->with('success', 'Vinyle ajouté avec succès');
    }

    public function edit(Vinyle $vinyle)
    {
        return view('vinyles.form', compact('vinyle'));
    }

    public function update(Request $request, Vinyle $vinyle)
    {
        $validated = $request->validate([
            'reference' => 'required|string|max:50|unique:vinyles,reference,' . $vinyle->id,
            'artiste' => 'required|string|max:255',
            'modele' => 'required|string|max:255',
            'genre' => 'nullable|string|max:100',
            'style' => 'nullable|string|max:100',
            'prix' => 'required|numeric|min:0',
            'quantite' => 'required|integer|min:0',
            'seuil_alerte' => 'required|integer|min:1',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'delete_photos' => 'nullable|array',
        ]);

        $vinyle->update($validated);

        // Supprimer les photos cochées
        if ($request->has('delete_photos')) {
            $photos = $vinyle->getMedia('photo');
            if ($photos) {
                foreach ($request->input('delete_photos') as $mediaId) {
                    if ($media = $photos->find($mediaId)) {
                        $media->delete();
                    }
                }
            }
        }

        // Upload nouvelles photos (respect max 3 total)
        $currentCount = $vinyle->getMedia('photo')->count();
        $maxNew = 3 - $currentCount;

        if ($request->hasFile('photos') && $maxNew > 0) {
            foreach (array_slice($request->file('photos'), 0, $maxNew) as $index => $photo) {
                $vinyle->addMedia($photo)
                    ->withCustomProperties(['order' => $currentCount + $index])
                    ->toMediaCollection('photo');
            }
        }

        return redirect()->route('vinyles.index')
            ->with('success', 'Vinyle modifié avec succès');
    }

    public function destroy(Vinyle $vinyle)
    {
        $vinyle->delete();

        return redirect()->route('vinyles.index')
            ->with('success', 'Vinyle supprimé avec succès');
    }

    public function kiosque(Request $request)
    {
        $allowedSorts = ['artiste', 'modele', 'prix', 'quantite', 'created_at'];
        $sort = $request->get('sort', 'artiste');
        
        // Protection injection SQL : whitelist des colonnes
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'artiste';
        }
        
        $search = $request->get('search', '');
        
        $vinylesQuery = Vinyle::with(['media']) // Eager loading des médias
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('artiste', 'like', "%{$search}%")
                        ->orWhere('modele', 'like', "%{$search}%")
                        ->orWhere('genre', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort)
            ->orderBy('modele');
        
        // Pagination pour éviter chargement trop grand (24 par page pour grille 4x6)
        $vinyles = $vinylesQuery->paginate(24)->withQueryString();

        // Redirection si page invalide (ex: page=2 mais une seule page de résultats)
        if ($vinyles->isEmpty() && $vinyles->currentPage() > 1) {
            return redirect()->route('kiosque.index');
        }

        // Transformer pour la vue (map pour avoir des tableaux, pas des stdClass)
        $vinylesData = $vinyles->getCollection()->map(function (Vinyle $vinyle) {
            return [
                'id'        => $vinyle->id,
                'artiste'   => $vinyle->artiste,
                'modele'    => $vinyle->modele,
                'prix'      => $vinyle->prix,
                'quantite'  => $vinyle->quantite,
                'image'     => $vinyle->getFirstMediaUrl('photo', 'medium'),
            ];
        })->all();

        return view('kiosque', [
            'vinylesData' => $vinylesData,
            'vinyles' => $vinyles, // Pour les liens de pagination
        ]);
    }
}
