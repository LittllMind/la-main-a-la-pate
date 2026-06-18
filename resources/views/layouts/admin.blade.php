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
                <div class="hidden sm:flex items-center gap-6 text-sm">
                    <a href="/" class="text-slate-300 hover:text-white transition">Site public</a>
                    <a href="{{ route('admin.panel') }}" class="text-slate-300 hover:text-white transition">Tableau de bord</a>
                    <a href="{{ route('admin.routes') }}" class="text-slate-300 hover:text-white transition">Routes</a>
                    <a href="{{ route('admin.posts.index') }}" class="text-slate-300 hover:text-white transition">Articles</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-slate-400 hover:text-red-400 text-sm transition">Deconnexion</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-6xl mx-auto w-full px-4 py-8">
        @yield('content')
    </main>

</body>
</html>
