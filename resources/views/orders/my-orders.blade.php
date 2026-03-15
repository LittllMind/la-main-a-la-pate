@extends('layouts.kiosque')

@section('title', 'Mes Commandes - Fundisc')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
            📦 Mes Commandes
        </h1>
        <p class="text-gray-400 mt-2">Consultez l'historique de vos commandes et leur statut</p>
    </div>

    @if($orders->isEmpty())
        <!-- Aucune commande -->
        <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-8 text-center">
            <div class="text-6xl mb-4">🛒</div>
            <h2 class="text-xl font-semibold text-gray-300 mb-2">Aucune commande pour le moment</h2>
            <p class="text-gray-400 mb-6">Vous n'avez pas encore passé de commande.</p>
            <a href="{{ route('kiosque.index') }}" 
               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl transition">
                🎵 Découvrir le catalogue
            </a>
        </div>
    @else
        <!-- Liste des commandes -->
        <div class="space-y-4">
            @foreach($orders as $order)
                <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6 hover:border-purple-500/50 transition">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Info commande -->
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg font-bold text-purple-400">{{ $order->numero_commande }}</span>
                                <span class="text-sm text-gray-500">•</span>
                                <span class="text-sm text-gray-400">{{ $order->created_at->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex items-center gap-2 mb-2">
                                @php
                                    $badgeClass = match($order->statut) {
                                        'en_attente' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
                                        'en_preparation' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
                                        'prete' => 'bg-green-500/20 text-green-400 border-green-500/30',
                                        'livree' => 'bg-gray-500/20 text-gray-400 border-gray-500/30',
                                        'annulee' => 'bg-red-500/20 text-red-400 border-red-500/30',
                                        default => 'bg-gray-500/20 text-gray-400 border-gray-500/30',
                                    };
                                    $badgeIcon = match($order->statut) {
                                        'en_attente' => '⏳',
                                        'en_preparation' => '🔧',
                                        'prete' => '✅',
                                        'livree' => '📦',
                                        'annulee' => '❌',
                                        default => '⭕',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium {{ $badgeClass }}">
                                    {{ $badgeIcon }} {{ $order->statutLabel() }}
                                </span>
                            </div>
                            <div class="text-gray-400 text-sm">
                                {{ $order->items->count() }} article(s) • 
                                <span class="font-semibold text-purple-400">{{ number_format($order->total, 2, ',', ' ') }} €</span>
                            </div>
                        </div>

                        <!-- Détail -->
                        <a href="#" 
                           onclick="document.getElementById('order-{{ $order->id }}').classList.toggle('hidden')"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-xl transition text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            Détails
                        </a>
                    </div>

                    <!-- Détails cachés -->
                    <div id="order-{{ $order->id }}" class="hidden mt-6 pt-6 border-t border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-4">Articles commandés</h3>
                        <div class="space-y-3">
                            @foreach($order->items as $item)
                                <div class="flex items-center gap-4 bg-gray-900/50 rounded-xl p-3">
                                    @if($item->vinyle)
                                        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-violet-600/30 to-fuchsia-600/30 flex items-center justify-center text-lg">
                                            💿
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-300">{{ $item->titre_vinyle }}</div>
                                            <div class="text-sm text-gray-500">
                                                {{ $item->quantite }} × {{ number_format($item->prix_unitaire, 2, ',', ' ') }} €
                                            </div>
                                        </div>
                                        <div class="font-semibold text-purple-400">
                                            {{ number_format($item->total, 2, ',', ' ') }} €
                                        </div>
                                    @else
                                        <div class="text-gray-500 italic">Article non disponible</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-700 flex justify-between items-center">
                            <span class="text-gray-400">Total</span>
                            <span class="text-xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                                {{ number_format($order->total, 2, ',', ' ') }} €
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection