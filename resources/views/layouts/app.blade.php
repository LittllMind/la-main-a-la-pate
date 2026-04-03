<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Fundisc')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col" x-data="{ mobileMenuOpen: false, adminMenuOpen: false }">

    <!-- Navigation -->
    <nav class="bg-gray-800/90 backdrop-blur-md fixed w-full z-50 border-b border-purple-500/30 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="{{ route('landing') }}" class="flex items-center space-x-2">
                    <span class="text-2xl">💿</span>
                    <span class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                        Fundisc
                    </span>
                </a>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="{{ route('landing') }}" class="transition {{ request()->routeIs('landing') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }}">
                        🏠 Accueil
                    </a>
                    <a href="{{ route('kiosque.index') }}" class="transition {{ request()->routeIs('kiosque.*') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }}">
                        🎵 Catalogue
                    </a>
                    <a href="{{ route('about') }}" class="transition {{ request()->routeIs('about') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }}">
                        💡 Le Concept
                    </a>
                    <a href="{{ route('contact') }}" class="transition {{ request()->routeIs('contact') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }}">
                        📧 Contact
                    </a>
                </div>
                
                <!-- Desktop Auth -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="{{ route('cart.index') }}" class="text-purple-400 hover:text-pink-400 transition relative">
                        🛒 Panier
                    </a>
                    
                    @guest
                        <a href="{{ route('login') }}" class="text-sm text-gray-300 hover:text-purple-400 transition">Connexion</a>
                        <a href="{{ route('register') }}" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm font-medium text-white transition">
                            🎧 S'inscrire
                        </a>
                    @else
                        <a href="{{ route('orders.my') }}" class="text-sm text-gray-300 hover:text-purple-400 transition {{ request()->routeIs('orders.my') ? 'text-pink-400 font-semibold' : '' }}">
                            📦 Mes commandes
                        </a>
                        
                        @if(Auth::user()->isAdmin() || Auth::user()->isEmploye())
                            <!-- Dropdown Admin -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false" class="flex items-center space-x-1 text-yellow-400 hover:text-yellow-300 transition font-semibold">
                                    <span>⚡ Admin</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-64 bg-gray-800 rounded-lg shadow-xl border border-purple-500/30 py-2 z-50">
                                    <!-- Dashboard -->
                                    <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-yellow-400 hover:bg-gray-700 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700 text-pink-400' : '' }}">
                                        📊 Tableau de bord
                                    </a>
                                    
                                    <!-- Gestion -->
                                    <div class="border-t border-gray-700 my-1"></div>
                                    <span class="block px-4 py-1 text-xs text-gray-500 uppercase tracking-wider">Gestion</span>
                                    <a href="{{ route('vinyles.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 {{ request()->routeIs('vinyles.*') ? 'bg-gray-700 text-pink-400' : '' }}">
                                        💿 Vinyles
                                    </a>
                                    <a href="{{ route('ventes.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 {{ request()->routeIs('ventes.*') ? 'bg-gray-700 text-pink-400' : '' }}">
                                        💰 Ventes
                                    </a>
                                    <a href="{{ route('admin.orders.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 {{ request()->routeIs('admin.orders.*') ? 'bg-gray-700 text-pink-400' : '' }}">
                                        📋 Commandes clients
                                    </a>
                                    <a href="{{ route('marche.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 {{ request()->routeIs('marche.*') ? 'bg-gray-700 text-pink-400' : '' }}">
                                        🏪 Mode Marché
                                    </a>
                                    <a href="{{ route('stock-alerts.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 {{ request()->routeIs('stock-alerts.*') ? 'bg-gray-700 text-pink-400' : '' }}">
                                        🚨 Alertes Stock
                                    </a>
                                    <a href="{{ route('fonds.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 {{ request()->routeIs('fonds.*') ? 'bg-gray-700 text-pink-400' : '' }}">
                                        🖼️ Fonds
                                    </a>
                                    
                                    <!-- Rapports -->
                                    <div class="border-t border-gray-700 my-1"></div>
                                    <span class="block px-4 py-1 text-xs text-gray-500 uppercase tracking-wider">Rapports</span>
                                    <a href="{{ route('stats') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 {{ request()->routeIs('stats') ? 'bg-gray-700 text-pink-400' : '' }}">
                                        📈 Statistiques
                                    </a>
                                    <a href="{{ route('admin.reports.stock') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 {{ request()->routeIs('admin.reports.stock') ? 'bg-gray-700 text-pink-400' : '' }}">
                                        📦 Stock
                                    </a>
                                    <a href="{{ route('admin.reports.artists') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 {{ request()->routeIs('admin.reports.artists') ? 'bg-gray-700 text-pink-400' : '' }}">
                                        🎤 Artistes
                                    </a>
                                    
                                    @if(Auth::user()->isAdmin())
                                        <!-- Administration -->
                                        <div class="border-t border-gray-700 my-1"></div>
                                        <span class="block px-4 py-1 text-xs text-gray-500 uppercase tracking-wider">Administration</span>
                                        <a href="{{ route('users.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 {{ request()->routeIs('users.*') ? 'bg-gray-700 text-pink-400' : '' }}">
                                            👥 Utilisateurs
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                        
                        <a href="{{ route('addresses.index') }}" class="text-gray-300 hover:text-purple-400 transition {{ request()->routeIs('addresses.*') ? 'text-pink-400' : '' }}" title="Mes adresses">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </a>
                        
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-gray-400 hover:text-red-400 transition">Déconnexion</button>
                        </form>
                    @endguest
                </div>
                
                <!-- Mobile menu button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-300 p-2">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg x-show="mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Mobile menu -->
            <div x-show="mobileMenuOpen" @click.away="mobileMenuOpen = false" x-cloak x-transition class="md:hidden mt-4 pb-4 space-y-3 border-t border-purple-500/30">
                <!-- Navigation principale -->
                <a href="{{ route('landing') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('landing') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2 pt-4">
                    🏠 Accueil
                </a>
                <a href="{{ route('kiosque.index') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('kiosque.*') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2">
                    🎵 Catalogue
                </a>
                <a href="{{ route('about') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('about') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2">
                    💡 Le Concept
                </a>
                <a href="{{ route('contact') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('contact') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2">
                    📧 Contact
                </a>
                
                <div class="border-t border-purple-500/30 pt-4 mt-4">
                    <a href="{{ route('cart.index') }}" @click="mobileMenuOpen = false" class="block text-purple-400 font-medium py-2">
                        🛒 Mon Panier
                    </a>
                </div>
                
                @auth
                    <div class="border-t border-purple-500/30 pt-4 space-y-3">
                        <a href="{{ route('orders.my') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('orders.my') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2">
                            📦 Mes commandes
                        </a>
                        <a href="{{ route('addresses.index') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('addresses.*') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2">
                            📍 Mes adresses
                        </a>
                        
                        @if(Auth::user()->isAdmin() || Auth::user()->isEmploye())
                            <!-- Section Admin Mobile -->
                            <div class="border-t border-purple-500/30 pt-4 mt-4">
                                <span class="block text-yellow-400 font-semibold py-2">⚡ Administration</span>
                                
                                <a href="{{ route('admin.dashboard') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('admin.dashboard') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2 pl-4">
                                    📊 Tableau de bord
                                </a>
                                
                                <span class="block text-xs text-gray-500 uppercase tracking-wider py-1 pl-4">Gestion</span>
                                <a href="{{ route('vinyles.index') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('vinyles.*') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2 pl-4">
                                    💿 Vinyles
                                </a>
                                <a href="{{ route('ventes.index') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('ventes.*') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2 pl-4">
                                    💰 Ventes
                                </a>
                                <a href="{{ route('admin.orders.index') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('admin.orders.*') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2 pl-4">
                                    📋 Commandes clients
                                </a>
                                <a href="{{ route('marche.index') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('marche.*') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2 pl-4">
                                    🏪 Mode Marché
                                </a>
                                <a href="{{ route('stock-alerts.index') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('stock-alerts.*') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2 pl-4">
                                    🚨 Alertes Stock
                                </a>
                                <a href="{{ route('fonds.index') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('fonds.*') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2 pl-4">
                                    🖼️ Fonds
                                </a>
                                
                                <span class="block text-xs text-gray-500 uppercase tracking-wider py-1 pl-4">Rapports</span>
                                <a href="{{ route('stats') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('stats') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2 pl-4">
                                    📈 Statistiques
                                </a>
                                <a href="{{ route('admin.reports.stock') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('admin.reports.stock') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2 pl-4">
                                    📦 Rapport Stock
                                </a>
                                <a href="{{ route('admin.reports.artists') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('admin.reports.artists') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2 pl-4">
                                    🎤 Rapport Artistes
                                </a>
                                
                                @if(Auth::user()->isAdmin())
                                    <span class="block text-xs text-gray-500 uppercase tracking-wider py-1 pl-4">Administration</span>
                                    <a href="{{ route('users.index') }}" @click="mobileMenuOpen = false" class="block {{ request()->routeIs('users.*') ? 'text-pink-400 font-semibold' : 'text-gray-300 hover:text-purple-400' }} py-2 pl-4">
                                        👥 Utilisateurs
                                    </a>
                                @endif
                            </div>
                        @endif
                        
                        <form method="POST" action="{{ route('logout') }}" class="pt-2">
                            @csrf
                            <button type="submit" class="text-red-400 py-2 hover:text-red-300">Déconnexion</button>
                        </form>
                    </div>
                @else
                    <div class="border-t border-purple-500/30 pt-4 mt-4 flex flex-col gap-3">
                        <a href="{{ route('login') }}" @click="mobileMenuOpen = false" class="block text-center text-gray-300 hover:text-purple-400 py-2 border border-purple-500/30 rounded-lg">
                            Connexion
                        </a>
                        <a href="{{ route('register') }}" @click="mobileMenuOpen = false" class="block text-center bg-purple-600 hover:bg-purple-700 py-2 rounded-lg font-medium text-white">
                            🎧 S'inscrire
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Spacer for fixed navbar -->
    <div class="h-16"></div>

    <!-- Page Content -->
    <main class="container mx-auto px-4 py-8 flex-grow">
        @if (session('success'))
            <div class="alert alert-success bg-green-600/90 text-white px-4 py-3 rounded-lg mb-4 shadow-lg">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error bg-red-600/90 text-white px-4 py-3 rounded-lg mb-4 shadow-lg">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 border-t border-purple-500/30 py-8 mt-auto">
        <div class="container mx-auto px-4 text-center text-gray-400">
            <p class="mb-2">💿 Fundisc - Passion Vinyle</p>
            <p class="text-sm">© 2026 - Artisanat & Musique</p>
        </div>
    </footer>

</body>
</html>
