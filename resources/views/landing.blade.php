<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Main à la Pâte — Veille IA Agentique</title>
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
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen">

    <!-- Navigation minimaliste -->
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

    <!-- Hero -->
    <section class="pt-32 pb-20 px-6">
        <div class="max-w-4xl mx-auto text-center">
            <div class="inline-block px-4 py-1.5 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-400 text-sm font-medium mb-8">
                🤖 Agents autonomes · 🧠 Mémoire persistante · ⚡ Actions concrètes
            </div>

            <h1 class="text-5xl md:text-7xl font-bold tracking-tight mb-8">
                <span class="gradient-text">L'IA agentique</span><br>
                <span class="text-white">sans le bullshit</span>
            </h1>

            <p class="text-xl text-gray-400 max-w-2xl mx-auto mb-12 leading-relaxed">
                Une veille technique sur l'intelligence artificielle agentique, épurée et pragmatique.
                Pour les amis tech du village et les curieux d'IA.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('blog.index') }}" class="bg-purple-600 hover:bg-purple-500 text-white px-8 py-4 rounded-xl font-semibold text-lg transition shadow-lg shadow-purple-500/20">
                    Lire la veille →
                </a>
                <a href="https://t.me/la_main_a_la_pate" target="_blank" class="flex items-center gap-2 bg-gray-800 hover:bg-gray-700 text-gray-300 px-8 py-4 rounded-xl font-semibold text-lg transition border border-gray-700">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    Recevoir sur Telegram
                </a>
            </div>
        </div>
    </section>

    <!-- Sections de contenu -->
    <section class="py-20 px-6 border-t border-gray-800">
        <div class="max-w-6xl mx-auto">
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-gray-900/50 rounded-2xl p-8 border border-gray-800 hover:border-purple-500/30 transition">
                    <div class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center mb-6 text-2xl">📝</div>
                    <h3 class="text-xl font-semibold mb-3">Articles techniques</h3>
                    <p class="text-gray-400 leading-relaxed">Retours d'expérience sur les agents IA, les architectures de mémoire persistante et les patterns de mise en production.</p>
                </div>
                <div class="bg-gray-900/50 rounded-2xl p-8 border border-gray-800 hover:border-purple-500/30 transition">
                    <div class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center mb-6 text-2xl">🔧</div>
                    <h3 class="text-xl font-semibold mb-3">Construit authentiquement</h3>
                    <p class="text-gray-400 leading-relaxed">Pas de jargon marketing. Du code, des benchmarks, des échecs et des solutions concrètes pour vos propres agents.</p>
                </div>
                <div class="bg-gray-900/50 rounded-2xl p-8 border border-gray-800 hover:border-purple-500/30 transition">
                    <div class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center mb-6 text-2xl">🌿</div>
                    <h3 class="text-xl font-semibold mb-3">Communauté locale</h3>
                    <p class="text-gray-400 leading-relaxed">Partagé avec les amis tech du village. Une veille accessible pour ceux qui veulent comprendre l'IA agentique sans bullshit.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA section -->
    <section class="py-20 px-6">
        <div class="max-w-3xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-6">Restez informé</h2>
            <p class="text-gray-400 mb-8 text-lg">Chaque nouveau post est partagé directement sur Telegram. Rejoignez la newsletter locale.</p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="https://t.me/+votre_groupe" target="_blank" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-xl font-semibold text-lg transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    Rejoindre sur Telegram
                </a>
                <a href="{{ route('blog.index') }}" class="w-full sm:w-auto border border-gray-700 hover:border-gray-500 text-gray-300 px-8 py-4 rounded-xl font-semibold text-lg transition">
                    Explorer les articles
                </a>
            </div>
        </div>
    </section>

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
