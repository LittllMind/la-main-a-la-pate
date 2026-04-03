@extends('layouts.kiosque')

@section('title', 'Dashboard - Fundisc')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
            👋 Bonjour, {{ Auth::user()->name }} !
        </h1>
        <p class="text-gray-400 mt-2">Rôle : <span class="text-purple-400">{{ Auth::user()->getRoleLabel() }}</span></p>
    </div>

    <!-- Section Client (Tous les rôles) -->
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-300 mb-4 flex items-center gap-2">
            🛍️ Espace Client
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Catalogue -->
            <a href="{{ route('kiosque.index') }}" 
               class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-6 hover:border-purple-500/50 hover:bg-gray-800 transition">
                <div class="text-4xl mb-3">🎵</div>
                <h3 class="text-lg font-semibold text-purple-400 group-hover:text-purple-300">Catalogue</h3>
                <p class="text-sm text-gray-400 mt-1">Parcourir les vinyles disponibles</p>
            </a>

            <!-- Panier -->
            <a href="{{ route('cart.index') }}" 
               class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-6 hover:border-purple-500/50 hover:bg-gray-800 transition">
                <div class="text-4xl mb-3">🛒</div>
                <h3 class="text-lg font-semibold text-purple-400 group-hover:text-purple-300">Mon Panier</h3>
                <p class="text-sm text-gray-400 mt-1">Gérer mon panier</p>
            </a>

            <!-- Mes commandes -->
            <a href="{{ route('orders.my') }}" 
               class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-6 hover:border-purple-500/50 hover:bg-gray-800 transition">
                <div class="text-4xl mb-3">📦</div>
                <h3 class="text-lg font-semibold text-purple-400 group-hover:text-purple-300">Mes Commandes</h3>
                <p class="text-sm text-gray-400 mt-1">Historique de mes commandes</p>
            </a>

            <!-- Mes adresses -->
            <a href="{{ route('addresses.index') }}" 
               class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-6 hover:border-purple-500/50 hover:bg-gray-800 transition">
                <div class="text-4xl mb-3">📍</div>
                <h3 class="text-lg font-semibold text-purple-400 group-hover:text-purple-300">Mes Adresses</h3>
                <p class="text-sm text-gray-400 mt-1">Gérer mes adresses de livraison</p>
            </a>
        </div>
    </div>

    @auth
        @if(Auth::user()->isEmployeOrAdmin())
        <!-- Section Admin/Employé -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-300 mb-4 flex items-center gap-2">
                🔧 Gestion du Stock
                <span class="text-sm font-normal text-gray-500">(Admin/Employé)</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Stock Vinyles -->
                <a href="{{ route('vinyles.index') }}" 
                   class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-6 hover:border-pink-500/50 hover:bg-gray-800 transition">
                    <div class="text-4xl mb-3">💿</div>
                    <h3 class="text-lg font-semibold text-pink-400 group-hover:text-pink-300">Stock Vinyles</h3>
                    <p class="text-sm text-gray-400 mt-1">Gérer le catalogue des vinyles</p>
                </a>

                <!-- Alertes Stock -->
                <a href="{{ route('stock-alerts.index') }}" 
                   class="group bg-gradient-to-br from-red-900/20 to-red-800/10 border border-red-700/30 rounded-2xl p-6 hover:border-red-500/50 hover:bg-red-900/20 transition">
                    <div class="text-4xl mb-3">🚨</div>
                    <h3 class="text-lg font-semibold text-red-400 group-hover:text-red-300">Alertes Stock</h3>
                    <p class="text-sm text-gray-400 mt-1">Suivi des ruptures et niveaux faibles</p>
                </a>

                <!-- Historique des mouvements -->
                <a href="{{ route('mouvements.index') }}" 
                   class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-6 hover:border-pink-500/50 hover:bg-gray-800 transition">
                    <div class="text-4xl mb-3">📋</div>
                    <h3 class="text-lg font-semibold text-pink-400 group-hover:text-pink-300">Mouvements Stock</h3>
                    <p class="text-sm text-gray-400 mt-1">Historique des entrées et sorties</p>
                </a>

                <!-- Stock Fonds -->
                <a href="{{ route('fonds.index') }}" 
                   class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-6 hover:border-pink-500/50 hover:bg-gray-800 transition">
                    <div class="text-4xl mb-3">🎨</div>
                    <h3 class="text-lg font-semibold text-pink-400 group-hover:text-pink-300">Stock Fonds</h3>
                    <p class="text-sm text-gray-400 mt-1">Gérer les fonds (miroirs, doré...)</p>
                </a>

                <!-- Ventes -->
                <a href="{{ route('ventes.index') }}" 
                   class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-6 hover:border-pink-500/50 hover:bg-gray-800 transition">
                    <div class="text-4xl mb-3">💰</div>
                    <h3 class="text-lg font-semibold text-pink-400 group-hover:text-pink-300">Ventes</h3>
                    <p class="text-sm text-gray-400 mt-1">Historique des ventes</p>
                </a>
            </div>
        </div>
        @endif

        @if(Auth::user()->isAdmin())
        <!-- Section Admin Only -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-300 mb-4 flex items-center gap-2">
                📊 Administration
                <span class="text-sm font-normal text-gray-500">(Admin uniquement)</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Statistiques -->
                <a href="{{ route('stats') }}" 
                   class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-6 hover:border-yellow-500/50 hover:bg-gray-800 transition">
                    <div class="text-4xl mb-3">📈</div>
                    <h3 class="text-lg font-semibold text-yellow-400 group-hover:text-yellow-300">Statistiques</h3>
                    <p class="text-sm text-gray-400 mt-1">CA, stocks, analyses</p>
                </a>
            </div>
        </div>
        @endif
    @endauth
</div>
@endsection