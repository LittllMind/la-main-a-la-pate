<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'La Main à la Pâte')</title>
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

    @include('layouts.navbar')

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
                    <h4 class="text-white font-semibold mb-2">La Main à la Pâte</h4>
                    <p class="text-slate-400 text-xs leading-relaxed">Site communautaire. Actualites, forums et echanges entre voisins.</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-2">Liens</h4>
                    <ul class="space-y-1 text-xs">
                        @auth
                            <li><a href="{{ route('subjects.tree.index') }}" class="hover:text-white transition">Arbre Sujets</a></li>
                            <li><a href="{{ route('documents.tree.documents') }}" class="hover:text-white transition">Documents</a></li>
                        @endauth
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-2">Legal</h4>
                    <ul class="space-y-1 text-xs">
                        <li><a href="{{ route('legal') }}" class="hover:text-white transition">Mentions legales</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-white transition">Politique de confidentialite</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-700 mt-6 pt-4 text-center text-xs text-slate-500">
                © {{ date('Y') }} La Main à la Pâte — Le Rozier
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
