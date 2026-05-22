@extends('layouts.public')

@section('title', 'À propos — La Main à la Pâte')

@section('content')
<div class="max-w-3xl mx-auto px-6 py-16">
    <header class="text-center mb-16">
        <h1 class="text-4xl font-bold mb-4">
            <span class="gradient-text">La Main à la Pâte</span>
        </h1>
        <p class="text-gray-400 text-lg">
            Une veille technique sur l'IA agentique, partagée sans filtre.
        </p>
    </header>

    <div class="space-y-12">
        <section class="bg-gray-900/50 rounded-2xl p-8 border border-gray-800">
            <h2 class="text-2xl font-semibold mb-4 flex items-center">
                <span class="mr-3">🎯</span> Le projet
            </h2>
            <p class="text-gray-400 leading-relaxed">
                La Main à la Pâte est un espace de veille technique dédié à l'intelligence artificielle agentique.
                Je partage ici mes expérimentations avec des agents autonomes, des architectures de mémoire persistante,
                et les outils qui émergent dans cet écosystème.
            </p>
        </section>

        <section class="bg-gray-900/50 rounded-2xl p-8 border border-gray-800">
            <h2 class="text-2xl font-semibold mb-4 flex items-center">
                <span class="mr-3">🤖</span> IA agentique ?
            </h2>
            <p class="text-gray-400 leading-relaxed mb-4">
                L'IA agentique, c'est quand une intelligence artificielle ne se contente plus de répondre à des questions —
                elle agit. Elle planifie, exécute des tâches, apprend de ses interactions, et évolue au fil du temps.
            </p>
            <p class="text-gray-400 leading-relaxed">
                C'est un domaine en explosion, entre les frameworks open source, les modèles de plus en plus capables,
                et les architectures distribuées qui permettent de déployer des agents sur plusieurs canaux.
            </p>
        </section>

        <section class="bg-gray-900/50 rounded-2xl p-8 border border-gray-800">
            <h2 class="text-2xl font-semibold mb-4 flex items-center">
                <span class="mr-3">🌿</span> Pour qui ?
            </h2>
            <p class="text-gray-400 leading-relaxed">
                Principalement pour les amis tech du village et les curieux qui veulent comprendre ce qui se passe
                dans l'IA sans passer par le jargon marketing. Pas de bullshit, pas de promesses irréalistes —
                juste du code, des tests et des retours d'expérience.
            </p>
        </section>

        <section class="bg-gray-900/50 rounded-2xl p-8 border border-gray-800">
            <h2 class="text-2xl font-semibold mb-4 flex items-center">
                <span class="mr-3">🛠️</span> Stack explorée
            </h2>
            <ul class="text-gray-400 space-y-2">
                <li class="flex items-start"><span class="text-purple-400 mr-2">▸</span> Hermes Agent — assistant avec mémoire persistante</li>
                <li class="flex items-start"><span class="text-purple-400 mr-2">▸</span> OpenClaw — gateway multi-plateformes</li>
                <li class="flex items-start"><span class="text-purple-400 mr-2">▸</span> Laravel + agents autonomes</li>
                <li class="flex items-start"><span class="text-purple-400 mr-2">▸</span> Modèles locaux (Ollama) et cloud</li>
            </ul>
        </section>
    </div>

    <div class="text-center mt-12">
        <a href="{{ route('blog.index') }}" class="inline-block bg-purple-600 hover:bg-purple-500 text-white px-8 py-3 rounded-xl font-medium transition">
            Lire les articles →
        </a>
    </div>
</div>
@endsection
