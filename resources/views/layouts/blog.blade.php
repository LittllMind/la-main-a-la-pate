<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'La Main à la Pâte')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .gradient-text {
            background: linear-gradient(135deg, #a78bfa 0%, #c084fc 50%, #e879f9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
    @stack('head')
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen">

    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 bg-gray-950/80 backdrop-blur-md border-b border-gray-800">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="/" class="text-xl font-bold tracking-tight">
                <span class="gradient-text">La Main à la Pâte</span>
            </a>
            <div class="flex items-center gap-8">
                <a href="{{ route('blog.index') }}" class="text-gray-400 hover:text-white transition">Veille</a>
                <a href="/kiosque" class="text-gray-400 hover:text-white transition">Kiosque</a>
                @auth
                    <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-gray-400 hover:text-white transition">Déconnexion</a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                @else
                    <a href="/login" class="text-gray-400 hover:text-white transition">Connexion</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-20 min-h-screen">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-800 py-8 px-6">
        <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-sm text-gray-500">
                © {{ date('Y') }} <span class="gradient-text font-semibold">La Main à la Pâte</span> — Veille IA Agentique
            </div>
            <div class="flex items-center gap-6 text-sm text-gray-500">
                <a href="/contact" class="hover:text-white transition">Contact</a>
                <a href="https://t.me/+votre_groupe" target="_blank" class="hover:text-white transition">Telegram</a>
            </div>
        </div>
    </footer>

</body>
</html>
