<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fundisc - Vinyles Hydrodécoupés</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css'])
    
    {{-- Schema.org --}}
    @include('components.schema-org')
</head>
<body class="bg-gray-900 text-white min-h-screen" x-data="{ mobileMenuOpen: false }">

    <!-- Navigation -->
    <nav class="bg-gray-800/90 backdrop-blur-md fixed w-full z-50 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="{{ route('landing') }}" class="flex items-center space-x-2">
                    <span class="text-2xl">💿</span>
                    <span class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-purple-400 to-pink-500 bg-clip-text text-transparent tracking-tight">
                        Fundisc
                    </span>
                </a>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('landing') }}" class="text-gray-300 hover:text-white transition">Accueil</a>
                    <a href="{{ route('kiosque.index') }}" class="text-gray-300 hover:text-white transition">Catalogue</a>
                    <a href="{{ route('about') }}" class="text-gray-300 hover:text-white transition">Le Concept</a>
                    <a href="{{ route('contact') }}" class="text-gray-300 hover:text-white transition">Contact</a>
                </div>
                
                <!-- Desktop Auth -->
                <div class="hidden md:flex items-center space-x-4">
                    @guest
                        <a href="{{ route('login') }}" class="text-sm text-gray-300 hover:text-white transition">Connexion</a>
                        <a href="{{ route('register') }}" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm font-medium transition">
                            S'inscrire
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="text-sm text-gray-300 hover:text-white transition">
                            {{ Auth::user()->name }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-gray-400 hover:text-white transition">Déconnexion</button>
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
            <div x-show="mobileMenuOpen" @click.away="mobileMenuOpen = false" x-cloak x-transition class="md:hidden mt-4 pb-4 space-y-3 border-t border-gray-700">
                <a href="{{ route('landing') }}" @click="mobileMenuOpen = false" class="block text-white font-medium py-2 pt-4">Accueil</a>
                <a href="{{ route('kiosque.index') }}" @click="mobileMenuOpen = false" class="block text-purple-400 font-semibold py-2">Catalogue</a>
                <a href="{{ route('about') }}" @click="mobileMenuOpen = false" class="block text-gray-300 hover:text-white py-2">Le Concept</a>
                <a href="{{ route('contact') }}" @click="mobileMenuOpen = false" class="block text-gray-300 hover:text-white py-2">Contact</a>
                
                @auth
                    <div class="border-t border-gray-700 pt-4 mt-4 space-y-3">
                        <a href="{{ route('cart.index') }}" @click="mobileMenuOpen = false" class="block text-gray-300 hover:text-white py-2">🛒 Mon Panier</a>
                        <a href="{{ route('orders.my') }}" @click="mobileMenuOpen = false" class="block text-gray-300 hover:text-white py-2">📦 Mes commandes</a>
                        <a href="{{ route('dashboard') }}" @click="mobileMenuOpen = false" class="block text-yellow-400 font-semibold py-2">🔧 Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}" class="pt-2">
                            @csrf
                            <button type="submit" class="text-red-400 py-2">Déconnexion</button>
                        </form>
                    </div>
                @else
                    <div class="border-t border-gray-700 pt-4 mt-4 flex flex-col gap-3">
                        <a href="{{ route('login') }}" @click="mobileMenuOpen = false" class="block text-center text-gray-300 hover:text-white py-2 border border-gray-600 rounded-lg">Connexion</a>
                        <a href="{{ route('register') }}" @click="mobileMenuOpen = false" class="block text-center bg-purple-600 hover:bg-purple-700 py-2 rounded-lg font-medium">
                            S'inscrire
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden">
        <!-- Background Animation -->
        <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900"></div>
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-20 left-10 w-64 h-64 bg-purple-500 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-pink-500 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        </div>

        <div class="relative z-10 text-center px-4 max-w-5xl mx-auto">
            <div class="mb-8">
                <span class="inline-block px-4 py-2 bg-purple-600/30 rounded-full text-purple-300 text-sm font-medium mb-6">
                    Collection Unique & Artisanale
                </span>
            </div>
            <h1 class="text-3xl sm:text-5xl md:text-7xl font-bold mb-4 sm:mb-6 leading-tight">
                <span class="bg-gradient-to-r from-purple-400 via-pink-400 to-purple-400 bg-clip-text text-transparent">
                    Découpez le son
                </span>
                <br>
                <span class="text-white">différemment</span>
            </h1>
            <p class="text-base sm:text-xl md:text-2xl text-gray-300 mb-8 sm:mb-10 max-w-3xl mx-auto leading-relaxed px-2 sm:px-0">
                Des vinyles hydrodécoupés à la main, transformés en œuvres d'art uniques. Chaque pièce raconte une histoire musicale.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center">
                <a href="{{ route('kiosque.index') }}" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 px-6 sm:px-8 py-4 rounded-xl text-lg font-semibold transition transform hover:scale-105 shadow-lg shadow-purple-500/30 text-center">
                    Explorer le Catalogue
                </a>
                <a href="{{ route('about') }}" class="bg-gray-800 hover:bg-gray-700 px-6 sm:px-8 py-4 rounded-xl text-lg font-semibold transition border border-gray-600 text-center">
                    En savoir plus
                </a>
            </div>

            <!-- Stats -->
            <div class="mt-12 sm:mt-16 grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-8">
                <div class="text-center p-4 bg-gray-800/30 rounded-xl sm:bg-transparent sm:rounded-none">
                    <div class="text-3xl sm:text-4xl font-bold text-purple-400 mb-1 sm:mb-2">{{ $stats['total'] }}+</div>
                    <div class="text-sm sm:text-base text-gray-400">Pièces disponibles</div>
                </div>
                <div class="text-center p-4 bg-gray-800/30 rounded-xl sm:bg-transparent sm:rounded-none">
                    <div class="text-3xl sm:text-4xl font-bold text-pink-400 mb-1 sm:mb-2">{{ $stats['recent'] }}</div>
                    <div class="text-sm sm:text-base text-gray-400">Nouveautés</div>
                </div>
                <div class="text-center p-4 bg-gray-800/30 rounded-xl sm:bg-transparent sm:rounded-none">
                    <div class="text-3xl sm:text-4xl font-bold text-purple-400 mb-1 sm:mb-2">100%</div>
                    <div class="text-sm sm:text-base text-gray-400">Artisanal</div>
                </div>
                <div class="text-center p-4 bg-gray-800/30 rounded-xl sm:bg-transparent sm:rounded-none">
                    <div class="text-3xl sm:text-4xl font-bold text-pink-400 mb-1 sm:mb-2">∞</div>
                    <div class="text-sm sm:text-base text-gray-400">Possibilités</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Section -->
    @if($featured->count() > 0)
    <section class="py-20 bg-gray-800/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold mb-4">En Vedette</h2>
                <p class="text-gray-400 text-lg">Nos dernières créations hydrodécoupées</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($featured as $vinyle)
                <div class="bg-gray-900 rounded-2xl overflow-hidden border border-gray-700 hover:border-purple-500 transition group">
                    <div class="aspect-square bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center relative overflow-hidden">
                        <div class="text-6xl group-hover:scale-110 transition duration-300">💿</div>
                        <div class="absolute top-4 right-4 px-3 py-1 {{ $vinyle->quantite > 0 ? 'bg-green-600' : 'bg-red-600' }} rounded-full text-xs font-medium">
                            {{ $vinyle->quantite > 0 ? 'Disponible' : 'Rupture' }}
                        </div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-2">{{ $vinyle->nom }}</h3>
                        <p class="text-gray-400 mb-4">{{ $vinyle->modele }}</p>
                        <div class="flex justify-between items-center">
                            <span class="text-2xl font-bold text-purple-400">{{ number_format($vinyle->prix, 2) }}€</span>
                            <a href="{{ route('kiosque.index') }}" class="text-purple-400 hover:text-purple-300 transition">
                                Voir détails →
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="text-center mt-12">
                <a href="{{ route('kiosque.index') }}" class="inline-block bg-gray-700 hover:bg-gray-600 px-8 py-3 rounded-xl font-semibold transition">
                    Voir tout le catalogue
                </a>
            </div>
        </div>
    </section>
    @endif

    <!-- Process Section -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4">L'Art de l'Hydrodécoupe</h2>
                <p class="text-gray-400 text-lg">Un processus artisanal unique</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="text-center">
                    <div class="w-20 h-20 bg-purple-600/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <span class="text-4xl">🔍</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Sélection</h3>
                    <p class="text-gray-400">Chaque vinyle est soigneusement choisi pour son potentiel artistique et son histoire musicale.</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-pink-600/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <span class="text-4xl">✂️</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Hydrodécoupe</h3>
                    <p class="text-gray-400">Technique de découpe à l'eau ultra-précise pour créer des formes et motifs uniques.</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-purple-600/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <span class="text-4xl">✨</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Finition</h3>
                    <p class="text-gray-400">Chaque pièce est finie à la main pour garantir une qualité exceptionnelle.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-purple-900/50 to-pink-900/50">
        <div class="max-w-4xl mx-auto text-center px-4">
            <h2 class="text-4xl font-bold mb-6">Prêt à découvrir votre pièce unique ?</h2>
            <p class="text-xl text-gray-300 mb-10">
                Parcourez notre catalogue et trouvez le vinyle hydrodécoupé qui vous correspond.
            </p>
            <a href="{{ route('kiosque.index') }}" class="inline-block bg-white text-gray-900 px-10 py-4 rounded-xl text-lg font-semibold hover:bg-gray-100 transition transform hover:scale-105">
                Explorer Maintenant
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 py-12 border-t border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <a href="{{ route('landing') }}" class="flex items-center space-x-2 mb-4 md:mb-0">
                    <span class="text-2xl">💿</span>
                    <span class="text-xl font-bold bg-gradient-to-r from-purple-400 to-pink-500 bg-clip-text text-transparent">Fundisc</span>
                </a>
                <div class="flex space-x-6 text-gray-400">
                    <a href="{{ route('about') }}" class="hover:text-white transition">À propos</a>
                    <a href="{{ route('contact') }}" class="hover:text-white transition">Contact</a>
                </div>
            </div>
            <div class="mt-8 text-center text-gray-500 text-sm">
                © {{ date('Y') }} Fundisc. Tous droits réservés.
            </div>
        </div>
    </footer>

</body>
</html>