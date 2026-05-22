@extends('layouts.public')

@section('title', 'Contact — La Main à la Pâte')

@section('content')
<div class="max-w-2xl mx-auto px-6 py-16">
    <header class="text-center mb-12">
        <h1 class="text-4xl font-bold mb-4">
            <span class="gradient-text">Contact</span>
        </h1>
        <p class="text-gray-400">
            Une question ? Une idée à partager ? Envie de discuter IA agentique ?
        </p>
    </header>

    <div class="space-y-8">
        <div class="bg-gray-900/50 rounded-2xl p-8 border border-gray-800">
            <h2 class="text-xl font-semibold mb-6 flex items-center">
                <span class="mr-3">📧</span> Email
            </h2>
            <p class="text-gray-400 mb-2">
                contact@la-main-a-la-pate.online
            </p>
            <p class="text-sm text-gray-500">
                Je réponds généralement sous 24-48h.
            </p>
        </div>

        <div class="bg-gray-900/50 rounded-2xl p-8 border border-gray-800">
            <h2 class="text-xl font-semibold mb-6 flex items-center">
                <span class="mr-3">💬</span> Telegram
            </h2>
            <p class="text-gray-400 mb-4">
                C'est là que je partage les nouveaux posts et où on peut discuter.
            </p>
            <a href="https://t.me/+votre_groupe" target="_blank" class="inline-flex items-center text-purple-400 hover:text-purple-300 transition">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                </svg>
                Rejoindre sur Telegram
            </a>
        </div>

        <div class="bg-gray-900/50 rounded-2xl p-8 border border-gray-800">
            <h2 class="text-xl font-semibold mb-6 flex items-center">
                <span class="mr-3">🐦</span> Twitter / X
            </h2>
            <p class="text-gray-400 mb-4">
                Des réflexions courtes et du partage.
            </p>
            <p class="text-gray-500 text-sm">
                @@aurelien_tisserand
            </p>
        </div>
    </div>

    <div class="text-center mt-12">
        <a href="{{ route('blog.index') }}" class="inline-block text-gray-500 hover:text-gray-300 transition">
            ← Retour aux articles
        </a>
    </div>
</div>
@endsection
