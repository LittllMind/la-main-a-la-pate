<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'La Main à la Pâte')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .gradient-text {
            background: linear-gradient(135deg, #a78bfa 0%, #c084fc 50%, #e879f9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #1e1b4b 0%, #111827 50%, #0f172a 100%);
        }
    </style>
    @stack('head')
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex flex-col">

    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 bg-gray-950/90 backdrop-blur-md border-b border-gray-800/50">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="/" class="text-lg font-bold tracking-tight">
                <span class="gradient-text">La Main à la Pâte</span>
            </a>
            <div class="flex items-center gap-6 text-sm">
                <a href="{{ route('blog.index') }}" class="text-gray-400 hover:text-white transition">Veille</a>
                <a href="/about" class="text-gray-400 hover:text-white transition">À propos</a>
                <a href="/contact" class="text-gray-400 hover:text-white transition">Contact</a>
                @auth
                    <a href="/dashboard" class="text-purple-400 hover:text-purple-300 transition">Dashboard</a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">@csrf
                        <button type="submit" class="text-gray-500 hover:text-red-400 transition text-sm">Déco</button>
                    </form>
                @else
                    <a href="/login" class="text-gray-400 hover:text-white transition">Connexion</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow pt-16">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-800/50 py-6 px-6 mt-auto">
        <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-gray-600">
                © {{ date('Y') }} <span class="gradient-text font-medium">La Main à la Pâte</span>
            </div>
            <div class="flex items-center gap-4 text-xs text-gray-600">
                <a href="/contact" class="hover:text-gray-400 transition">Contact</a>
                <a href="https://t.me/+votre_groupe" target="_blank" class="hover:text-gray-400 transition">Telegram</a>
            </div>
        </div>
    </footer>

</body>
</html>
