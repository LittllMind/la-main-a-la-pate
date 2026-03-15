{{-- resources/views/layouts/kiosque.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Fundisc')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col" x-data="{ mobileMenuOpen: false }">

    <!-- Navigation -->
    <nav class="bg-gray-800/90 backdrop-blur-sm border-b border-gray-700 sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="/" class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                    💿 Fundisc
                </a>
                <div class="hidden sm:flex items-center gap-6">
                    <a href="/kiosque" class="hover:text-purple-400 transition">Catalogue</a>
                    <a href="/about" class="hover:text-purple-400 transition">Le Concept</a>
                    <a href="/contact" class="hover:text-purple-400 transition">Contact</a>
                    @auth
                        <a href="/cart" class="hover:text-purple-400 transition">Panier</a>
                        <a href="{{ route('orders.my') }}" class="hover:text-purple-400 transition">Mes commandes</a>
                        <a href="/dashboard" class="text-yellow-400 hover:text-yellow-300 font-semibold">🔧 Dashboard</a>
                        <a href="/addresses" class="hover:text-purple-400 transition" title="Mes adresses">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </a>
                        <form action="/logout" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-red-400 hover:text-red-300 transition">Déconnexion</button>
                        </form>
                    @else
                        <a href="/login" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 px-4 py-2 rounded-lg transition">Connexion</a>
                    @endauth
                </div>
                <!-- Mobile menu button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="sm:hidden text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            <!-- Mobile menu -->
            <div x-show="mobileMenuOpen" @click.away="mobileMenuOpen = false" x-cloak class="sm:hidden mt-4 space-y-2">
                <a href="/kiosque" class="block text-purple-400 font-semibold py-2">Catalogue</a>
                <a href="/about" class="block hover:text-purple-400 py-2">Le Concept</a>
                <a href="/contact" class="block hover:text-purple-400 py-2">Contact</a>
                @auth
                    <a href="/cart" class="block hover:text-purple-400 py-2">Panier</a>
                    <a href="{{ route('orders.my') }}" class="block hover:text-purple-400 py-2">Mes commandes</a>
                    <a href="/dashboard" class="block text-yellow-400 py-2">🔧 Dashboard</a>
                    <a href="/addresses" class="block hover:text-purple-400 py-2">Mes adresses</a>
                    <form action="/logout" method="POST">
                        @csrf
                        <button type="submit" class="text-red-400 py-2">Déconnexion</button>
                    </form>
                @else
                    <a href="/login" class="block bg-gradient-to-r from-purple-600 to-pink-600 px-4 py-2 rounded-lg text-center">Connexion</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main class="container mx-auto px-4 py-6 sm:py-8 flex-grow">
        @if (session('success'))
            <div class="alert alert-success bg-green-600 text-white px-4 py-3 rounded-2xl mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error bg-red-600 text-white px-4 py-3 rounded-2xl mb-4">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 border-t border-gray-700 py-6 mt-auto">
        <div class="container mx-auto px-4 text-center text-gray-400">
            <p>© 2026 Fundisc - Artisanat & Passion</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
