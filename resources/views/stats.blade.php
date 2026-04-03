@extends('layouts.kiosque')

@section('title', 'Statistiques - Vinyle Hydrodécoupé')

@section('content')<div class="max-w-7xl mx-auto" x-data="{ periode: '{{ $periode }}' }">

    <!-- Header avec filtre -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                📊 Statistiques
            </h1>
            <p class="text-gray-400 mt-2">Vue d'ensemble de l'activité • <span class="text-purple-400 font-medium">{{ $periodeLabel }}</span></p>
        </div>

        <!-- Filtre de période -->
        <form method="GET" action="{{ route('stats') }}" class="flex items-center gap-3">
            <label class="text-sm text-gray-400">Période :</label>
            <select name="periode" @change="$event.target.form.submit()" 
                    class="px-4 py-2 bg-gray-800 border border-gray-600 rounded-xl text-sm focus:border-purple-500 focus:outline-none">
                <option value="30j" {{ $periode === '30j' ? 'selected' : '' }}>30 derniers jours</option>
                <option value="3m" {{ $periode === '3m' ? 'selected' : '' }}>3 derniers mois</option>
                <option value="12m" {{ $periode === '12m' ? 'selected' : '' }}>12 derniers mois</option>
                <option value="all" {{ $periode === 'all' ? 'selected' : '' }}>Depuis le début</option>
            </select>
        </form>
    </div>

    <!-- Cartes principales -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-300 mb-4 flex items-center gap-2">
            💰 Vue d'ensemble</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- CA total réalisé -->
            <div class="bg-gradient-to-br from-purple-600/20 to-pink-600/20 border border-purple-500/30 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-3xl">💳</span>
                    <span class="text-xs font-medium text-purple-400 bg-purple-500/20 px-2 py-1 rounded-full">Total</span>
                </div>
                <div class="text-2xl font-bold text-white">{{ number_format($chiffreAffairesTotal, 0, ',', ' ') }} €</div>
                <div class="text-sm text-gray-400 mt-1">CA total réalisé</div>
            </div>

            <!-- CA période -->
            <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-2xl">📈</span>
                    <span class="text-xs font-medium text-green-400 bg-green-500/20 px-2 py-1 rounded-full">{{ $periodeLabel }}</span>
                </div>
                <div class="text-2xl font-bold text-white">{{ number_format($chiffreAffaires, 0, ',', ' ') }} €</div>
                <div class="text-sm text-gray-400 mt-1">Chiffre d'affaires sur la période</div>
            </div>

            <!-- Ventes période -->
            <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6">
                <div class="text-3xl mb-2">🧾</div>
                <div class="text-2xl font-bold text-white">{{ $totalVentes }}</div>
                <div class="text-sm text-gray-400 mt-1">Ventes sur la période</div>
            </div>

            <!-- Panier moyen -->
            <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6">
                <div class="text-3xl mb-2">🛒</div>
                <div class="text-2xl font-bold text-white">{{ number_format($panierMoyen, 2, ',', ' ') }} €</div>
                <div class="text-sm text-gray-400 mt-1">Panier moyen</div>
            </div>
        </div>
    </div>

    <!-- Stocks & Alertes -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-300 mb-4 flex items-center gap-2">
            📦 Stock &amp; Alertes</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Valeur stock catalogue -->
            <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6">
                <div class="text-3xl mb-2">🏷️</div>
                <div class="text-xl font-bold text-purple-400">
                    {{ number_format($valeurStock, 0, ',', ' ') }} €
                </div>
                <div class="text-sm text-gray-400 mt-1">Valeur stock (prix catalogue)</div>
            </div>

            <!-- Total vinyles stock -->
            <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6">
                <div class="text-3xl mb-2">💿</div>
                <div class="text-xl font-bold text-white">{{ $totalQuantiteVinyles }}</div>
                <div class="text-sm text-gray-400 mt-1">Vinyles en stock</div>
            </div>

            <!-- Stock bas (lien) -->
            <a href="{{ route('vinyles.index', ['filter' => 'stock_bas']) }}" 
               class="block bg-yellow-500/10 border border-yellow-500/30 rounded-2xl p-6 hover:bg-yellow-500/20 transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-3xl">⚠️</span>
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
                <div class="text-xl font-bold text-yellow-400">{{ $stockBas }}</div>
                <div class="text-sm text-yellow-400/70 mt-1">Stock bas (≤ 3)</div>
            </a>

            <!-- Ruptures (lien) -->
            <a href="{{ route('vinyles.index', ['filter' => 'rupture']) }}" 
               class="block bg-red-500/10 border border-red-500/30 rounded-2xl p-6 hover:bg-red-500/20 transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-3xl">🚨</span>
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
                <div class="text-xl font-bold text-red-400">{{ $rupturesStock }}</div>
                <div class="text-sm text-red-400/70 mt-1">Ruptures de stock</div>
            </a>
        </div>
    </div>

    <!-- Détail Stocks -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-300 mb-4 flex items-center gap-2">
            📊 Détail par Type</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Vinyles vendus -->
            <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6">
                <div class="text-2xl mb-2">💿</div>
                <div class="text-xl font-bold text-white">{{ $quantiteVinylesVendus }}</div>
                <div class="text-sm text-gray-400">Vinyles vendus</div>
            </div>

            <!-- Fonds miroir -->
            <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-2xl">✨</span>
                    <span class="text-xs text-gray-500">Miroir</span>
                </div>
                <div class="text-xl font-bold text-blue-400">{{ $quantiteFondsMiroirStock }}</div>
                <div class="text-sm text-gray-400">Stock / {{ $quantiteFondsMiroirVendus }} vendus</div>
            </div>

            <!-- Fonds doré -->
            <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-2xl">🌟</span>
                    <span class="text-xs text-gray-500">Doré</span>
                </div>
                <div class="text-xl font-bold text-yellow-400">{{ $quantiteFondsDoreStock }}</div>
                <div class="text-sm text-gray-400">Stock / {{ $quantiteFondsDoreVendus }} vendus</div>
            </div>

            <!-- Total fonds vendus -->
            <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6">
                <div class="text-2xl mb-2">🪞</div>
                <div class="text-xl font-bold text-white">{{ $quantiteFondsVendusTotal }}</div>
                <div class="text-sm text-gray-400">Fonds vendus (total)</div>
            </div>
        </div>
    </div>

    <!-- Marges & Rentabilité -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-300 mb-4 flex items-center gap-2">
            💹 Marges &amp; Rentabilité</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Marge brute période -->
            <div class="bg-green-500/10 border border-green-500/30 rounded-2xl p-6">
                <div class="text-3xl mb-2">📈</div>
                <div class="text-xl font-bold text-green-400">{{ number_format($margeBrute, 0, ',', ' ') }} €</div>
                <div class="text-sm text-gray-400 mt-1">Marge brute (période)</div>
            </div>

            <!-- Marge brute totale -->
            <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6">
                <div class="text-2xl mb-2">📊</div>
                <div class="text-xl font-bold text-white">{{ number_format($margeBruteTotale, 0, ',', ' ') }} €</div>
                <div class="text-sm text-gray-400">Marge brute historique</div>
            </div>

            <!-- Taux marge -->
            <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6">
                <div class="text-2xl mb-2">🎯</div>
                <div class="text-xl font-bold text-purple-400">{{ number_format($tauxMargeBruteTotale, 1, ',', ' ') }}%</div>
                <div class="text-sm text-gray-400">Taux de marge</div>
            </div>

            <!-- Marge potentielle -->
            <div class="bg-gradient-to-br from-purple-600/10 to-pink-600/10 border border-purple-500/30 rounded-2xl p-6">
                <div class="text-2xl mb-2">🚀</div>
                <div class="text-xl font-bold text-purple-400">{{ number_format($margePotentielleStock, 0, ',', ' ') }} €</div>
                <div class="text-sm text-gray-400 mt-1">Marge potentielle sur stock</div>
            </div>
        </div>
    </div>

    <!-- Graphiques (si données) -->
    @if($topModelesVendus->count() > 0)
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-300 mb-4 flex items-center gap-2">
            📉 Top des Ventes</h2>

        <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6">
            <div class="space-y-3">
                @foreach($topModelesVendus->take(10) as $modele)
                    <div class="flex items-center gap-4">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center text-sm font-bold text-white">
                            {{ $loop->iteration }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-gray-300 font-medium">{{ $modele->nom }}</span>
                                <span class="text-purple-400 font-semibold">{{ $modele->total_vendus }} vendus</span>
                            </div>
                            <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-purple-500 to-pink-500 rounded-full" 
                                     style="width: {{ ($modele->total_vendus / $topModelesVendus->first()->total_vendus) * 100 }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Retour -->
    <div class="flex justify-center">
        <a href="{{ route('dashboard') }}" 
           class="inline-flex items-center gap-2 px-6 py-3 bg-gray-800 hover:bg-gray-700 border border-gray-600 rounded-xl transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour au Dashboard
        </a>
    </div>

</div>
@endsection