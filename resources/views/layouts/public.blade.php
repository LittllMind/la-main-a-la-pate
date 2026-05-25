<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'La Main a la Pate')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
    @stack('head')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="flex justify-between h-14 items-center">
                <a href="/" class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                    La Main a la Pate
                </a>
                <div class="hidden sm:flex items-center gap-6 text-sm">
                    <a href="/" class="text-slate-600 hover:text-slate-900 transition">Accueil</a>
                    <a href="/seraphotheque" class="text-slate-600 hover:text-slate-900 transition">Seraphotheque</a>
                    @auth
                        <a href="{{ route('community.index') }}" class="text-slate-600 hover:text-slate-900 transition">Communaute</a>
                        <a href="/dashboard" class="text-slate-600 hover:text-slate-900 transition">Tableau de bord</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-slate-500 hover:text-red-600 text-sm transition">Deconnexion</button>
                        </form>
                    @else
                        <a href="/a-propos" class="text-slate-600 hover:text-slate-900 transition">A propos</a>
                        <a href="/contact" class="text-slate-600 hover:text-slate-900 transition">Contact</a>
                        <a href="/login" class="text-slate-600 hover:text-slate-900 transition">Connexion</a>
                        <a href="/register" class="bg-slate-900 text-white px-3 py-1.5 rounded-md text-sm hover:bg-slate-700 transition">S'inscrire</a>
                    @endauth
                </div>
                <button id="mobile-menu-btn" class="sm:hidden text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>
            <div id="mobile-menu" class="hidden sm:hidden pb-4 space-y-2 text-sm">
                <a href="/" class="block text-slate-600 hover:text-slate-900 py-1">Accueil</a>
                <a href="/seraphotheque" class="block text-slate-600 hover:text-slate-900 py-1">Seraphotheque</a>
                @auth
                    <a href="{{ route('community.index') }}" class="block text-slate-600 hover:text-slate-900 py-1">Communaute</a>
                    <a href="/dashboard" class="block text-slate-600 hover:text-slate-900 py-1">Tableau de bord</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-slate-500 hover:text-red-600 py-1">Deconnexion</button>
                    </form>
                @else
                    <a href="/a-propos" class="block text-slate-600 hover:text-slate-900 py-1">A propos</a>
                    <a href="/contact" class="block text-slate-600 hover:text-slate-900 py-1">Contact</a>
                    <a href="/login" class="block text-slate-600 hover:text-slate-900 py-1">Connexion</a>
                    <a href="/register" class="block text-slate-900 font-medium py-1">S'inscrire</a>
                @endauth
            </div>
        </div>
    </nav>

    @if(session('success'))
        <div class="max-w-5xl mx-auto px-4 mt-4">
            <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-lg border border-emerald-200 text-sm">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <main class="flex-grow w-full">
        @yield('content')
    </main>

    <footer class="bg-slate-900 text-slate-300 mt-auto">
        <div class="max-w-5xl mx-auto px-4 py-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
                <div>
                    <h4 class="text-white font-semibold mb-2">La Main a la Pate</h4>
                    <p class="text-slate-400 text-xs leading-relaxed">Site communautaire du Rozier. Actualites, forums et echanges entre voisins.</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-2">Liens</h4>
                    <ul class="space-y-1 text-xs">
                        <li><a href="/" class="hover:text-white transition">Accueil</a></li>
                        <li><a href="/seraphotheque" class="hover:text-white transition">Seraphotheque</a></li>
                        <li><a href="/a-propos" class="hover:text-white transition">A propos</a></li>
                        <li><a href="/contact" class="hover:text-white transition">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-2">Legal</h4>
                    <ul class="space-y-1 text-xs">
                        <li><a href="/mentions-legales" class="hover:text-white transition">Mentions legales</a></li>
                        <li><a href="/confidentialite" class="hover:text-white transition">Politique de confidentialite</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-700 mt-6 pt-4 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} La Main a la Pate — Le Rozier
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>