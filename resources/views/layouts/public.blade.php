<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'La Main a la Pate')</title>
@php
    $isDev = in_array(request()->getHost(), ['127.0.0.1', 'localhost', '10.5.0.2']) || str_contains(request()->getHost(), ':8000') || str_contains(request()->getHost(), ':8001');
@endphp
@if($isDev)
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-dev-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon-dev.png">
@else
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
@endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
        [x-cloak] { display: none !important; }
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
                @auth
                    <a href="/sujets" class="text-slate-600 hover:text-slate-900 transition">Sujets</a>
                    <a href="/documents/arbre" class="text-slate-600 hover:text-slate-900 transition">Documents</a>
                    <a href="/sujets/arbre" class="text-slate-600 hover:text-slate-900 transition">Arbre sujets</a>
                    <a href="/dashboard" class="text-slate-600 hover:text-slate-900 transition">Tableau de bord</a>
                    <a href="/profile" class="text-slate-600 hover:text-slate-900 transition">Mon profil</a>

                    @admin
                        <a href="{{ route('admin.panel') }}" class="text-slate-600 hover:text-slate-900 transition">admin</a>
                    @endadmin

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-slate-500 hover:text-red-600 text-sm transition">Deconnexion</button>
                    </form>
                @else
                    <a href="/seraphotheque" class="text-slate-600 hover:text-slate-900 transition">Seraphotheque</a>
                    <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-900 transition">Connexion</a>
                @endauth
                </div>
                <button id="mobile-menu-btn" class="sm:hidden text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>
            <div id="mobile-menu" class="hidden sm:hidden pb-4 space-y-2 text-sm">
                @auth
                    <a href="/sujets" class="block text-slate-600 hover:text-slate-900 py-1">Sujets</a>
                    <a href="/documents/arbre" class="block text-slate-600 hover:text-slate-900 py-1">Documents</a>
                    <a href="/sujets/arbre" class="block text-slate-600 hover:text-slate-900 py-1">Arbre sujets</a>
                    <a href="/dashboard" class="block text-slate-600 hover:text-slate-900 py-1">Tableau de bord</a>
                    <a href="/profile" class="block text-slate-600 hover:text-slate-900 py-1">Mon profil</a>
                    @admin
                        <a href="{{ route('admin.panel') }}" class="block text-slate-600 hover:text-slate-900 py-1">admin</a>
                    @endadmin
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-slate-500 hover:text-red-600 py-1">Deconnexion</button>
                    </form>
                @else
                    <!-- A propos / Contact / Connexion / S'inscrire masqués le temps de formalisation -->
                    <a href="/seraphotheque" class="block text-slate-600 hover:text-slate-900 py-1">Seraphotheque</a>
                    <a href="{{ route('login') }}" class="block text-slate-600 hover:text-slate-900 py-1">Connexion</a>
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
                    <p class="text-slate-400 text-xs leading-relaxed">Site communautaire. Actualites, forums et echanges entre voisins.</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-2">Liens</h4>
                    <ul class="space-y-1 text-xs">
                        @auth
                            <li><a href="/sujets" class="hover:text-white transition">Sujets</a></li>
                            <li><a href="/documents/arbre" class="hover:text-white transition">Documents</a></li>
                            <li><a href="/sujets/arbre" class="hover:text-white transition">Arbre sujets</a></li>
                        @endauth
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

    @stack('scripts')
    <script>
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>
