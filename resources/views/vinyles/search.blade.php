@extends('layouts.app')

@section('title', 'Recherche de Vinyles')

@section('content')
<div class="container mx-auto px-4 py-8">
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-100">Recherche de Vinyles</h1>
        <a href="{{ route('vinyles.index') }}" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
            ← Retour à la liste
        </a>
    </div>

    <!-- Barre de recherche et filtres -->
    <div class="bg-gray-800 rounded-lg shadow border border-gray-700 mb-6 p-6">
        <form action="{{ route('vinyles.search') }}" method="GET" id="search-form">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <!-- Recherche texte -->
                <div class="lg:col-span-2">
                    <label for="q" class="block text-sm font-medium text-gray-300 mb-1">Recherche</label>
                    <div class="relative">
                        <input type="text" name="q" id="q" value="{{ request('q') }}" 
                               placeholder="Artiste, album ou référence..."
                               class="w-full bg-gray-700 border-gray-600 text-white rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50 pl-10">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Filtre Genre -->
                <div>
                    <label for="genre" class="block text-sm font-medium text-gray-300 mb-1">Genre</label>
                    <select name="genre" id="genre" class="w-full bg-gray-700 border-gray-600 text-white rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50">
                        <option value="">Tous les genres</option>
                        @foreach($genres as $genre)
                            <option value="{{ $genre }}" {{ request('genre') == $genre ? 'selected' : '' }}>
                                {{ $genre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtre Style -->
                <div>
                    <label for="style" class="block text-sm font-medium text-gray-300 mb-1">Style</label>
                    <select name="style" id="style" class="w-full bg-gray-700 border-gray-600 text-white rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50">
                        <option value="">Tous les styles</option>
                        @foreach($styles as $style)
                            <option value="{{ $style }}" {{ request('style') == $style ? 'selected' : '' }}>
                                {{ $style }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <!-- Prix min -->
                <div>
                    <label for="prix_min" class="block text-sm font-medium text-gray-300 mb-1">Prix min (€)</label>
                    <input type="number" name="prix_min" id="prix_min" value="{{ request('prix_min') }}" 
                           placeholder="0" min="0" step="0.01"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50">
                </div>

                <!-- Prix max -->
                <div>
                    <label for="prix_max" class="block text-sm font-medium text-gray-300 mb-1">Prix max (€)</label>
                    <input type="number" name="prix_max" id="prix_max" value="{{ request('prix_max') }}" 
                           placeholder="1000" min="0" step="0.01"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50">
                </div>

                <!-- Année -->
                <div>
                    <label for="annee" class="block text-sm font-medium text-gray-300 mb-1">Année d'ajout</label>
                    <select name="annee" id="annee" class="w-full bg-gray-700 border-gray-600 text-white rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50">
                        <option value="">Toutes les années</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee }}" {{ request('annee') == $annee ? 'selected' : '' }}>
                                {{ $annee }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tri -->
                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-300 mb-1">Trier par</label>
                    <select name="sort" id="sort" class="w-full bg-gray-700 border-gray-600 text-white rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-500 focus:ring-opacity-50">
                        <option value="date_ajout_desc" {{ request('sort') == 'date_ajout_desc' ? 'selected' : '' }}>Date d'ajout (récent)</option>
                        <option value="date_ajout_asc" {{ request('sort') == 'date_ajout_asc' ? 'selected' : '' }}>Date d'ajout (ancien)</option>
                        <option value="prix_asc" {{ request('sort') == 'prix_asc' ? 'selected' : '' }}>Prix (croissant)</option>
                        <option value="prix_desc" {{ request('sort') == 'prix_desc' ? 'selected' : '' }}>Prix (décroissant)</option>
                        <option value="artiste" {{ request('sort') == 'artiste' ? 'selected' : '' }}>Artiste (A-Z)</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('vinyles.search') }}" class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg transition">
                    Réinitialiser
                </a>
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition">
                    Rechercher
                </button>
            </div>
        </form>
    </div>

    <!-- Résultats -->
    <div class="bg-gray-800 rounded-lg shadow border border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700">
            <p class="text-sm text-gray-300">
                @if($vinyles->total() > 0)
                    {{ $vinyles->total() }} vinyle(s) trouvé(s)
                @else
                    Aucun vinyle trouvé
                @endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-700">
                <thead class="bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Image</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Référence</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Artiste</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Modèle</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Genre/Style</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Prix</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-800 divide-y divide-gray-700" id="vinyles-results">
                    @forelse($vinyles as $vinyle)
                        <tr class="hover:bg-gray-700 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <img src="{{ $vinyle->image }}" alt="{{ $vinyle->nom_complet }}" class="h-16 w-16 object-cover rounded">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-200">
                                {{ $vinyle->reference }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-200">
                                {{ $vinyle->artiste }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                {{ $vinyle->modele ?: '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                {{ $vinyle->genre ?: '-' }}<br>
                                <span class="text-xs text-gray-500">{{ $vinyle->style ?: '-' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-200">
                                {{ number_format($vinyle->prix, 2) }} €
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $vinyle->stock_status_class }}">
                                    {{ $vinyle->quantite }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="{{ route('vinyles.show', $vinyle) }}" class="text-blue-400 hover:text-blue-300" title="Voir">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('vinyles.edit', $vinyle) }}" class="text-yellow-400 hover:text-yellow-300" title="Modifier">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-lg font-medium text-gray-300">Aucun vinyle trouvé</p>
                                    <p class="text-sm text-gray-500 mt-1">Essayez de modifier vos critères de recherche</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4" id="pagination">
        {{ $vinyles->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-submit form on select change (for Ajax-like experience)
    document.querySelectorAll('#search-form select').forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('search-form').submit();
        });
    });
</script>
@endpush
