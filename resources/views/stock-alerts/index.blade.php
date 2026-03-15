{{-- resources/views/stock-alerts/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Alertes Stock - Fundisc')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
            🚨 Alertes Stock
        </h1>
        <p class="text-gray-400 mt-2">Gestion multicritère des alertes et seuils de stock</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-br from-red-900/50 to-red-800/30 border border-red-700/50 rounded-2xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-300 text-xs font-medium">Ruptures</p>
                    <p class="text-2xl font-bold text-red-400 mt-1">{{ $stats['ruptures'] }}</p>
                </div>
                <div class="text-3xl">⛔</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-900/50 to-yellow-800/30 border border-yellow-700/50 rounded-2xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-300 text-xs font-medium">Stock Faible</p>
                    <p class="text-2xl font-bold text-yellow-400 mt-1">{{ $stats['faibles'] }}</p>
                </div>
                <div class="text-3xl">⚠️</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-900/50 to-purple-800/30 border border-purple-700/50 rounded-2xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-300 text-xs font-medium">Actives</p>
                    <p class="text-2xl font-bold text-purple-400 mt-1">{{ $stats['total_actives'] }}</p>
                </div>
                <div class="text-3xl">🔔</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-gray-800/50 to-gray-700/30 border border-gray-600/50 rounded-2xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-300 text-xs font-medium">Aujourd'hui</p>
                    <p class="text-2xl font-bold text-gray-400 mt-1">{{ $stats['aujourdhui'] }}</p>
                </div>
                <div class="text-3xl">📅</div>
            </div>
        </div>
    </div>

    <!-- Filtres Avancés -->
    <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                Filtres Multicritères
            </h3>
            @if(request()->hasAny(['type', 'produit', 'statut', 'date_debut', 'date_fin', 'search']))
                <a href="{{ route('stock-alerts.index') }}" class="text-sm text-purple-400 hover:text-purple-300 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Réinitialiser
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('stock-alerts.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <!-- Type d'alerte -->
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Type d'alerte</label>
                    <select name="type" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500">
                        <option value="tous" {{ $filtres['type'] === 'tous' ? 'selected' : '' }}>Tous les types</option>
                        <option value="rupture" {{ $filtres['type'] === 'rupture' ? 'selected' : '' }}>⛔ Rupture</option>
                        <option value="faible" {{ $filtres['type'] === 'faible' ? 'selected' : '' }}>⚠️ Stock Faible</option>
                    </select>
                </div>

                <!-- Type de produit -->
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Produit</label>
                    <select name="produit" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500">
                        <option value="tous" {{ $filtres['produit'] === 'tous' ? 'selected' : '' }}>Tous les produits</option>
                        <option value="vinyle" {{ $filtres['produit'] === 'vinyle' ? 'selected' : '' }}>💿 Vinyles</option>
                        <option value="fond" {{ $filtres['produit'] === 'fond' ? 'selected' : '' }}>🎨 Fonds</option>
                    </select>
                </div>

                <!-- Statut -->
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Statut</label>
                    <select name="statut" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500">
                        <option value="actif" {{ $filtres['statut'] === 'actif' ? 'selected' : '' }}>🟢 Actives</option>
                        <option value="resolu" {{ $filtres['statut'] === 'resolu' ? 'selected' : '' }}>✅ Résolues</option>
                        <option value="tous" {{ $filtres['statut'] === 'tous' ? 'selected' : '' }}>Tous les statuts</option>
                    </select>
                </div>

                <!-- Date début -->
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Depuis</label>
                    <input type="date" name="date_debut" value="{{ $filtres['date_debut'] }}" 
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500">
                </div>

                <!-- Date fin -->
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Jusqu'à</label>
                    <input type="date" name="date_fin" value="{{ $filtres['date_fin'] }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500">
                </div>

                <!-- Trier par -->
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Trier par</label>
                    <select name="sort" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500">
                        <option value="date_desc" {{ $filtres['sort'] === 'date_desc' ? 'selected' : '' }}>Date ↓ (récent)</option>
                        <option value="date_asc" {{ $filtres['sort'] === 'date_asc' ? 'selected' : '' }}>Date ↑ (ancien)</option>
                        <option value="type" {{ $filtres['sort'] === 'type' ? 'selected' : '' }}>Type d'alerte</option>
                        <option value="produit" {{ $filtres['sort'] === 'produit' ? 'selected' : '' }}>Type de produit</option>
                    </select>
                </div>
            </div>

            <!-- Recherche + Boutons -->
            <div class="flex flex-col md:flex-row gap-4 pt-2">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ $filtres['search'] }}" placeholder="🔍 Rechercher par nom, artiste ou référence..."
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-purple-500">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white px-6 py-2 rounded-lg transition font-medium">
                        Filtrer
                    </button>
                    <a href="{{ route('stock-alerts.export', request()->query()) }}" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        CSV
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Badges filtres actifs -->
    @if(request()->hasAny(['type', 'produit', 'search', 'date_debut']))
    <div class="flex flex-wrap gap-2 mb-6">
        @if(request('type') && request('type') !== 'tous')
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-purple-900/50 text-purple-300 border border-purple-700">
                Type: {{ request('type') === 'rupture' ? '⛔ Rupture' : '⚠️ Faible' }}
            </span>
        @endif
        @if(request('produit') && request('produit') !== 'tous')
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-purple-900/50 text-purple-300 border border-purple-700">
                Produit: {{ request('produit') === 'vinyle' ? '💿 Vinyles' : '🎨 Fonds' }}
            </span>
        @endif
        @if(request('search'))
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-pink-900/50 text-pink-300 border border-pink-700">
                🔍 "{{ request('search') }}"
            </span>
        @endif
        @if(request('date_debut'))
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-gray-700 text-gray-300 border border-gray-600">
                📅 Depuis {{ request('date_debut') }}
            </span>
        @endif
    </div>
    @endif

    <!-- Alerte Rupture -->
    @if($outOfStockItems->isNotEmpty() && !$filtres['type'] || $filtres['type'] === 'tous' || $filtres['type'] === 'rupture')
    <div class="bg-red-900/20 border border-red-700/50 rounded-2xl p-6 mb-8">
        <h3 class="text-xl font-bold text-red-400 mb-4 flex items-center gap-2">
            ⛔ Ruptures de stock ({{ $stats['ruptures'] }})
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($outOfStockItems->take(6) as $vinyle)
            <div class="bg-gray-800/50 rounded-xl p-4 flex items-center justify-between hover:bg-gray-700/50 transition">
                <div class="flex items-center gap-3">
                    @if($vinyle->getFirstMediaUrl('photo'))
                        <img src="{{ $vinyle->getFirstMediaUrl('photo') }}" alt="" class="w-12 h-12 object-cover rounded-lg">
                    @else
                        <div class="w-12 h-12 bg-gray-700 rounded-lg flex items-center justify-center">💿</div>
                    @endif
                    <div>
                        <p class="font-semibold text-white text-sm">{{ $vinyle->nom }}</p>
                        <p class="text-xs text-gray-400">{{ $vinyle->artiste }}</p>
                        <p class="text-xs text-red-400 mt-1">Stock : {{ $vinyle->quantite }}</p>
                    </div>
                </div>
                <a href="{{ route('vinyles.edit', $vinyle) }}" class="text-purple-400 hover:text-purple-300 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </a>
            </div>
            @endforeach
        </div>
        @if($outOfStockItems->count() > 6)
            <p class="text-center text-red-400/60 text-sm mt-4">+ {{ $outOfStockItems->count() - 6 }} autres ruptures...</p>
        @endif
    </div>
    @endif

    <!-- Alerte Stock Faible -->
    @if($lowStockItems->isNotEmpty() && (!$filtres['type'] || $filtres['type'] === 'tous' || $filtres['type'] === 'faible'))
    <div class="bg-yellow-900/20 border border-yellow-700/50 rounded-2xl p-6 mb-8">
        <h3 class="text-xl font-bold text-yellow-400 mb-4 flex items-center gap-2">
            ⚠️ Stocks faibles ({{ $stats['faibles'] }})
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($lowStockItems->take(6) as $vinyle)
            <div class="bg-gray-800/50 rounded-xl p-4 flex items-center justify-between hover:bg-gray-700/50 transition">
                <div class="flex items-center gap-3">
                    @if($vinyle->getFirstMediaUrl('photo'))
                        <img src="{{ $vinyle->getFirstMediaUrl('photo') }}" alt="" class="w-12 h-12 object-cover rounded-lg">
                    @else
                        <div class="w-12 h-12 bg-gray-700 rounded-lg flex items-center justify-center">💿</div>
                    @endif
                    <div>
                        <p class="font-semibold text-white text-sm">{{ $vinyle->nom }}</p>
                        <p class="text-xs text-gray-400">{{ $vinyle->artiste }}</p>
                        <p class="text-xs text-yellow-400 mt-1">Stock : {{ $vinyle->quantite }} / Seuil : {{ $vinyle->seuil_alerte ?? 1 }}</p>
                    </div>
                </div>
                <a href="{{ route('vinyles.edit', $vinyle) }}" class="text-purple-400 hover:text-purple-300 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </a>
            </div>
            @endforeach
        </div>
        @if($lowStockItems->count() > 6)
            <p class="text-center text-yellow-400/60 text-sm mt-4">+ {{ $lowStockItems->count() - 6 }} autres stocks faibles...</p>
        @endif
    </div>
    @endif

    <!-- Tableau des Alertes -->
    <div class="bg-gray-800/50 border border-gray-700 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white">
                Alertes filtrées 
                <span class="text-purple-400">({{ $alerts->total() }})</span>
            </h3>
            <div class="flex gap-3">
                <a href="{{ route('stock-alerts.history') }}" class="text-purple-400 hover:text-purple-300 text-sm flex items-center gap-1">
                    📜 Historique
                </a>
            </div>
        </div>

        @if($alerts->isEmpty())
            <div class="p-8 text-center text-gray-500">
                <p class="text-4xl mb-4">✅</p>
                <p>Aucune alerte ne correspond aux critères sélectionnés.</p>
                <a href="{{ route('stock-alerts.index') }}" class="text-purple-400 hover:text-purple-300 mt-2 inline-block">
                    Réinitialiser les filtres →
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Produit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Alerte</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Détails Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($alerts as $alert)
                        <tr class="hover:bg-gray-700/30 {{ $alert->statut === 'resolu' ? 'opacity-50' : '' }}">
                            <td class="px-6 py-4">
                                @if($alert->alertable)
                                    <div class="flex items-center gap-3">
                                        @if($alert->alertable_type === 'App\\Models\\Vinyle' && $alert->alertable->getFirstMediaUrl('photo'))
                                            <img src="{{ $alert->alertable->getFirstMediaUrl('photo') }}" alt="" class="w-10 h-10 object-cover rounded-lg">
                                        @else
                                            <div class="w-10 h-10 bg-gray-700 rounded-lg flex items-center justify-center text-lg">
                                                {{ $alert->alertable_type === 'App\\Models\\Vinyle' ? '💿' : '🎨' }}
                                            </div>
                                        @endif
                                        <div>
                                            <a href="{{ route('vinyles.show', $alert->alertable) }}" class="text-purple-400 hover:text-purple-300 font-medium">
                                                {{ $alert->alertable->nom }}
                                            </a>
                                            @if($alert->alertable_type === 'App\\Models\\Vinyle')
                                                <p class="text-xs text-gray-500">{{ $alert->alertable->artiste }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-500">Produit supprimé</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    {{ $alert->alertable_type === 'App\\Models\\Vinyle' ? 'bg-purple-900/50 text-purple-300 border border-purple-700' : 'bg-pink-900/50 text-pink-300 border border-pink-700' }}">
                                    {{ $alert->alertable_type === 'App\\Models\\Vinyle' ? 'Vinyle' : 'Fond' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($alert->quantite_actuelle <= 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-900/50 text-red-400 border border-red-700">
                                        ⛔ Rupture
                                    </span>
                                @elseif($alert->quantite_actuelle <= $alert->seuil_alerte)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-900/50 text-yellow-400 border border-yellow-700">
                                        ⚠️ Faible
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-700 text-gray-400">
                                        ℹ️ Info
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-300">
                                <span class="{{ $alert->quantite_actuelle <= 0 ? 'text-red-400 font-bold' : ($alert->quantite_actuelle <= $alert->seuil_alerte ? 'text-yellow-400' : '') }}">
                                    {{ $alert->quantite_actuelle }}
                                </span>
                                <span class="text-gray-500">/ {{ $alert->seuil_alerte }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-400 text-sm">
                                {{ $alert->created_at->format('d/m/y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($alert->statut === 'actif')
                                    <form action="{{ route('stock-alerts.resolve', $alert) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-green-400 hover:text-green-300 text-sm flex items-center gap-1 ml-auto bg-green-900/30 hover:bg-green-900/50 px-3 py-1 rounded-lg transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Résoudre
                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-500 text-sm flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Résolue {{ $alert->resolved_at?->diffForHumans() }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-700 bg-gray-900/30">
                {{ $alerts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
