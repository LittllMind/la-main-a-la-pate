<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Liste des adresses de l'utilisateur
     */
    public function index()
    {
        $addresses = Auth::user()->addresses()->orderBy('is_default', 'desc')->orderBy('created_at', 'desc')->get();
        
        return view('addresses.index', compact('addresses'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        return view('addresses.create');
    }

    /**
     * Sauvegarder une nouvelle adresse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'nom' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telephone' => 'required|string|max:20',
            'adresse' => 'required|string|max:500',
            'code_postal' => 'required|string|max:10',
            'ville' => 'required|string|max:255',
            'pays' => 'required|string|max:2',
            'instructions' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['is_default'] = $validated['is_default'] ?? false;

        // Si c'est l'adresse par défaut, désactiver les autres
        if ($validated['is_default']) {
            Address::where('user_id', Auth::id())->update(['is_default' => false]);
        }

        $address = Address::create($validated);

        return redirect()->route('addresses.index')
            ->with('success', 'Adresse ajoutée avec succès !');
    }

    /**
     * Afficher une adresse
     */
    public function show($id)
    {
        $address = Auth::user()->addresses()->findOrFail($id);
        return view('addresses.show', compact('address'));
    }

    /**
     * Formulaire d'édition
     */
    public function edit($id)
    {
        $address = Auth::user()->addresses()->findOrFail($id);
        return view('addresses.edit', compact('address'));
    }

    /**
     * Mettre à jour une adresse
     */
    public function update(Request $request, $id)
    {
        $address = Auth::user()->addresses()->findOrFail($id);

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'nom' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telephone' => 'required|string|max:20',
            'adresse' => 'required|string|max:500',
            'code_postal' => 'required|string|max:10',
            'ville' => 'required|string|max:255',
            'pays' => 'required|string|max:2',
            'instructions' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ]);

        $validated['is_default'] = $validated['is_default'] ?? false;

        // Si c'est l'adresse par défaut, désactiver les autres
        if ($validated['is_default']) {
            Address::where('user_id', Auth::id())
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $address->update($validated);

        return redirect()->route('addresses.index')
            ->with('success', 'Adresse mise à jour !');
    }

    /**
     * Supprimer une adresse
     */
    public function destroy($id)
    {
        $address = Auth::user()->addresses()->findOrFail($id);
        
        // Empêcher la suppression de l'adresse par défaut
        if ($address->is_default) {
            return redirect()->route('addresses.index')
                ->with('error', 'Impossible de supprimer l\'adresse par défaut. Définissez d\'abord une autre adresse par défaut.');
        }

        $address->delete();

        return redirect()->route('addresses.index')
            ->with('success', 'Adresse supprimée !');
    }

    /**
     * Définir comme adresse par défaut
     */
    public function setDefault($id)
    {
        $address = Auth::user()->addresses()->findOrFail($id);
        
        // Désactiver toutes les adresses
        Address::where('user_id', Auth::id())->update(['is_default' => false]);
        
        // Activer celle-ci
        $address->update(['is_default' => true]);

        return redirect()->route('addresses.index')
            ->with('success', 'Adresse par défaut définie !');
    }
}
