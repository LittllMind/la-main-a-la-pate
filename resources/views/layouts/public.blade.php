<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'La Main a la Pate')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
    @stack('head')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="flex justify-between h-14 items-center">
                <a href="/" class="text-lg font-bold text-slate-900 tracking-tight">
                    La Main a la Pate
                </a>
                <div class="hidden sm:flex items-center gap-6 text-sm">
                    <a href="/" class="text-slate-600 hover:text-slate-900">Actualites</a>
                    <a href="{{ route('community.index') }}" class="text-slate-600 hover:text-slate-900">Communaute</a>
                    @auth
                        <a href="/dashboard" class="text-slate-600 hover:text-slate-900">Tableau de bord</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-slate-500 hover:text-red-600 text-sm">Deconnexion</button>
                        </form>
                    @else
                        <a href="/login" class="text-slate-600 hover:text-slate-900">Connexion</a>
                        <a href="/register" class="bg-slate-900 text-white px-3 py-1.5 rounded-md text-sm hover:bg-slate-700">S'inscrire</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    @if(session('success'))
        <div class="max-w-5xl mx-auto px-4 mt-4">
            <div class="bg-green-50 text-green-800 px-4 py-3 rounded-lg border border-green-200 text-sm">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <main class="flex-grow max-w-5xl mx-auto w-full px-4 py-8">
        @yield('content')
    </main>

    <footer class="bg-white border-t border-slate-200 mt-auto">
        <div class="max-w-5xl mx-auto px-4 py-6 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} La Main a la Pate — Le Rozier
        </div>
    </footer>
</body>
</html>
