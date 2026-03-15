@extends('layouts.app')

@section('title', 'Paiement - Commande')

@section('content')
<div class="min-h-screen bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        <!-- En-tête -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-violet-400 to-pink-400 bg-clip-text text-transparent">
                Récapitulatif de commande
            </h1>
            <p class="mt-2 text-gray-400">Étape 3/3 : Paiement sécurisé</p>
        </div>

        <!-- Progression -->
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white text-sm">✓</div>
                    <span class="ml-2 text-green-400 text-sm">Panier</span>
                </div>
                <div class="w-16 h-1 bg-green-500"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white text-sm">✓</div>
                    <span class="ml-2 text-green-400 text-sm">Livraison</span>
                </div>
                <div class="w-16 h-1 bg-violet-500"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-violet-500 flex items-center justify-center text-white text-sm font-bold">3</div>
                    <span class="ml-2 text-violet-400 text-sm font-semibold">Paiement</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Colonne gauche : Récapitulatif -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Adresse de livraison -->
                <div class="bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-2xl font-bold text-white">📍 Adresse de livraison</h2>
                        <a href="{{ route('orders.create') }}" class="text-sm text-violet-400 hover:text-violet-300 transition-colors">
                            Modifier
                        </a>
                    </div>
                    
                    <div class="space-y-2 text-gray-300">
                        <p class="font-semibold text-white">{{ $shipping['nom'] }}</p>
                        <p>{{ $shipping['adresse'] }}</p>
                        <p>{{ $shipping['code_postal'] }} {{ $shipping['ville'] }}</p>
                        <p>{{ $shipping['pays'] === 'FR' ? 'France' : ($shipping['pays'] === 'BE' ? 'Belgique' : ($shipping['pays'] === 'CH' ? 'Suisse' : ($shipping['pays'] === 'LU' ? 'Luxembourg' : ($shipping['pays'] === 'DE' ? 'Allemagne' : 'Autre')))) }}</p>
                        <p class="text-sm text-gray-400 mt-2">
                            📧 {{ $shipping['email'] }} | 📱 {{ $shipping['telephone'] }}
                        </p>
                        @if(!empty($shipping['instructions']))
                            <p class="text-sm text-gray-400 mt-2 pt-2 border-t border-gray-700">
                                📝 Instructions : {{ $shipping['instructions'] }}
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Articles commandés -->
                <div class="bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-700">
                    <h2 class="text-2xl font-bold text-white mb-4">📦 Articles commandés</h2>
                    
                    <div class="space-y-4">
                        @foreach($cart->items as $item)
                            <div class="flex items-center space-x-4 p-4 bg-gray-900 rounded-xl border border-gray-700">
                                <!-- Image placeholder -->
                                <div class="w-20 h-20 bg-gradient-to-br from-violet-600 to-pink-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                    </svg>
                                </div>
                                
                                <!-- Infos vinyle -->
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-white">{{ $item->vinyle->nom ?? 'Vinyle inconnu' }}</h3>
                                    <p class="text-sm text-gray-400">Quantité : {{ $item->quantite }}</p>
                                    <p class="text-xs mt-1">
                                        @if($item->fond_id && $item->fond)
                                            <span class="text-pink-400">
                                                ✨ Avec fond {{ $item->fond->nom }} (+{{ number_format($item->fond->prix_achat, 2) }} €)
                                            </span>
                                        @else
                                            <span class="text-gray-500">
                                                📀 Vinyle simple
                                            </span>
                                        @endif
                                    </p>
                                </div>
                                
                                <!-- Prix -->
                                <div class="text-right">
                                    <p class="text-lg font-bold bg-gradient-to-r from-violet-400 to-pink-400 bg-clip-text text-transparent">
                                        {{ number_format($item->prix_unitaire * $item->quantite, 2) }} €
                                    </p>
                                    <p class="text-xs text-gray-500">{{ number_format($item->prix_unitaire, 2) }} € / unité</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Mode de paiement -->
                <div class="bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-700">
                    <h2 class="text-2xl font-bold text-white mb-4">💳 Mode de paiement</h2>
                    
                    <div class="space-y-4">
                        <div class="p-4 bg-gradient-to-r from-violet-900/50 to-pink-900/50 rounded-xl border border-violet-500/30">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-8 bg-white rounded flex items-center justify-center">
                                        <span class="text-xs font-bold text-violet-600">CB</span>
                                    </div>
                                    <div>
                                        <p class="text-white font-semibold">Carte bancaire</p>
                                        <p class="text-xs text-gray-400">Visa, Mastercard, American Express</p>
                                    </div>
                                </div>
                                <div class="text-green-400 text-sm font-semibold">✓ Sélectionné</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2 text-xs text-gray-500 justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span>Paiement crypté et sécurisé par Stripe</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne droite : Total et action -->
            <div class="lg:col-span-1">
                <div class="bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-700 sticky top-8">
                    <h2 class="text-xl font-bold text-white mb-6">Récapitulatif financier</h2>

                    <!-- Totaux -->
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-sm text-gray-400">
                            <span>Sous-total ({{ $cart->items->count() }} article{{ $cart->items->count() > 1 ? 's' : '' }})</span>
                            <span>{{ number_format($cart->total, 2) }} €</span>
                        </div>
                        
                        <div class="flex justify-between text-sm text-gray-400">
                            <span>Livraison</span>
                            <span class="text-green-400">Gratuite</span>
                        </div>
                        
                        <div class="flex justify-between text-sm text-gray-400">
                            <span>Frais de traitement</span>
                            <span class="text-green-400">Offerts</span>
                        </div>
                        
                        <div class="border-t border-gray-700 pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-white">Total à payer</span>
                                <span class="text-2xl font-bold bg-gradient-to-r from-violet-400 to-pink-400 bg-clip-text text-transparent">
                                    {{ number_format($cart->total, 2) }} €
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Bouton de paiement -->
                    <form action="{{ route('payment.checkout') }}" method="POST">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order->id ?? '' }}">
                        <button type="submit"
                            class="w-full px-6 py-4 bg-gradient-to-r from-violet-600 to-pink-600 hover:from-violet-500 hover:to-pink-500 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg mb-4 flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span>Payer maintenant</span>
                        </button>
                    </form>

                    <!-- Bouton retour -->
                    <a href="{{ route('orders.create') }}"
                        class="w-full px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-xl transition-colors text-center block mb-6">
                        ← Retour
                    </a>

                    <!-- Garantie -->
                    <div class="pt-6 border-t border-gray-700">
                        <div class="space-y-3 text-xs text-gray-400">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                <span>Achat sécurisé et crypté</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                <span>Satisfait ou remboursé (14 jours)</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                                <span>Paiement CB sécurisé</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
