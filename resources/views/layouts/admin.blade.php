<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Administration')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
        .route-method-GET { background-color: #10b981; color: white; }
        .route-method-POST { background-color: #3b82f6; color: white; }
        .route-method-PUT { background-color: #f59e0b; color: white; }
        .route-method-PATCH { background-color: #8b5cf6; color: white; }
        .route-method-DELETE { background-color: #ef4444; color: white; }
    </style>
    @stack('head')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <nav class="bg-slate-900 border-b border-slate-700 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="flex justify-between h-14 items-center">
                <a href="/" class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>
                    La Main a la Pate — Admin
                </a>

                <!-- Hamburger mobile -->
                <button id="admin-mobile-menu-btn" class="sm:hidden text-slate-300 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <!-- Desktop nav -->
                <div class="hidden sm:flex items-center gap-6 text-sm">
                    <a href="{{ route('admin.panel') }}" class="{{ request()->routeIs('admin.panel') ? 'text-white font-medium border-b-2 border-emerald-400 pb-0.5' : 'text-slate-300 hover:text-white transition' }}">Tableau de bord</a>
                    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'text-white font-medium border-b-2 border-emerald-400 pb-0.5' : 'text-slate-300 hover:text-white transition' }}">Utilisateurs</a>
                    <a href="{{ route('admin.posts.index') }}" class="{{ request()->routeIs('admin.posts.*') ? 'text-white font-medium border-b-2 border-emerald-400 pb-0.5' : 'text-slate-300 hover:text-white transition' }}">Articles</a>
                    <a href="{{ route('admin.sections.index') }}" class="{{ request()->routeIs('admin.sections.*') ? 'text-white font-medium border-b-2 border-emerald-400 pb-0.5' : 'text-slate-300 hover:text-white transition' }}">Sections</a>
                    <a href="{{ route('subjects.index') }}" class="{{ request()->routeIs('subjects.*') ? 'text-white font-medium border-b-2 border-emerald-400 pb-0.5' : 'text-slate-300 hover:text-white transition' }}">Sujets</a>
                    <a href="{{ route('admin.routes') }}" class="{{ request()->routeIs('admin.routes') ? 'text-white font-medium border-b-2 border-emerald-400 pb-0.5' : 'text-slate-300 hover:text-white transition' }}">Routes</a>
                    <a href="{{ url('/') }}" class="text-slate-300 hover:text-white transition">Site public</a>

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-slate-400 hover:text-red-400 text-sm transition">Deconnexion</button>
                    </form>
                </div>
            </div>

            <!-- Mobile nav -->
            <div id="admin-mobile-menu" class="hidden sm:hidden pb-4 space-y-2 text-sm">
                <a href="{{ route('admin.panel') }}" class="{{ request()->routeIs('admin.panel') ? 'block text-white font-medium py-1' : 'block text-slate-300 py-1' }}">Tableau de bord</a>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'block text-white font-medium py-1' : 'block text-slate-300 py-1' }}">Utilisateurs</a>
                <a href="{{ route('admin.posts.index') }}" class="{{ request()->routeIs('admin.posts.*') ? 'block text-white font-medium py-1' : 'block text-slate-300 py-1' }}">Articles</a>
                <a href="{{ route('admin.sections.index') }}" class="{{ request()->routeIs('admin.sections.*') ? 'block text-white font-medium py-1' : 'block text-slate-300 py-1' }}">Sections</a>
                <a href="{{ route('subjects.index') }}" class="{{ request()->routeIs('subjects.*') ? 'block text-white font-medium py-1' : 'block text-slate-300 py-1' }}">Sujets</a>
                <a href="{{ route('admin.routes') }}" class="{{ request()->routeIs('admin.routes') ? 'block text-white font-medium py-1' : 'block text-slate-300 py-1' }}">Routes</a>
                <a href="{{ url('/') }}" class="block text-slate-400 py-1">Site public</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-400 hover:text-red-400 py-1 text-left">Deconnexion</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-6xl mx-auto w-full px-4 py-8">
        @yield('content')
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('admin-mobile-menu-btn');
        if (btn) {
            btn.addEventListener('click', function() {
                document.getElementById('admin-mobile-menu').classList.toggle('hidden');
            });
        }
    });
    </script>

</body>
</html>
