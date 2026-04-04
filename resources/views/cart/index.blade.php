{{-- resources/views/cart/index.blade.php --}}
@php
    /** @var \App\Services\CartService $cartService */
    $cartService = app(\App\Services\CartService::class);
    $cart = $cartService->getCart();
@endphp

@extends('layouts.app')

@section('title', 'Mon Panier - Vinyle Hydrodécoupé')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
            🛒 Mon Panier
        </h1>
        <a href="{{ route('kiosque.index') }}" class="text-purple-300 hover:text-pink-300 transition">
            ← Continuer mes achats
        </a>
    </div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Messages de succès/erreur --}}
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-900/30 border border-green-500 text-green-300 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-900/30 border border-red-500 text-red-300 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Alertes stock --}}
            @if (!empty($stockErrors))
                <div class="mb-4 p-4 bg-yellow-900/30 border border-yellow-500 text-yellow-300 rounded-lg">
                    <p class="font-semibold mb-2">⚠️ Problèmes de stock :</p>
                    <ul class="list-disc list-inside">
                        @foreach ($stockErrors as $error)
                            <li>{{ is_array($error) ? ($error['message'] ?? $error) : $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($cart->isEmpty())
                {{-- Panier vide --}}
                <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700 overflow-hidden rounded-xl">
                    <div class="p-6 text-center">
                        <div class="text-6xl mb-4">🛒</div>
                        <h3 class="text-xl font-semibold text-gray-200 mb-2">
                            Votre panier est vide
                        </h3>
                        <p class="text-gray-400 mb-6">
                            Découvrez notre sélection de vinyles vintage
                        </p>
                        <a href="{{ url('/kiosque') }}"
                            class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-lg hover:from-purple-700 hover:to-pink-700 transition font-semibold">
                            Voir les vinyles
                        </a>
                    </div>
                </div>
            @else
                {{-- Panier avec articles --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- Colonne principale : Liste des articles --}}
                    <div class="lg:col-span-2">
                        <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700 overflow-hidden rounded-xl">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold bg-gradient-to-r from-purple-300 to-pink-300 bg-clip-text text-transparent mb-4">
                                    Articles ({{ $cart->totalItems }})
                                </h3>

                                <div class="space-y-4">
                                    @foreach ($cart->items as $item)
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between py-4 border-b border-gray-700 gap-4">
                                            {{-- Info article --}}
                                            <div class="flex-1">
                                                <div class="font-bold text-gray-100 text-lg">
                                                    {{ $item->vinyle->nom ?? 'Vinyle' }}
                                                </div>
                                                <div class="text-sm text-purple-300 mt-1">
                                                    Fond :
                                                    @if ($item->fond)
                                                        {{ ucfirst($item->fond->type) }}
                                                    @else
                                                        Standard
                                                    @endif
                                                </div>
                                                <div class="text-sm text-gray-400 mt-1">
                                                    {{ number_format($item->prix_unitaire, 2, ',', ' ') }} € / unité
                                                </div>
                                            </div>

                                            {{-- Contrôles quantité --}}
                                            <div class="flex items-center gap-4">
                                                {{-- Formulaire mise à jour quantité --}}
                                                <form method="POST" action="{{ route('cart.update', $item->id) }}" class="flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label for="qty-{{ $item->id }}" class="sr-only">Quantité</label>
                                                    <div class="flex items-center">
                                                        <button type="button"
                                                            onclick="decrementQty('qty-{{ $item->id }}')"
                                                            class="w-8 h-8 rounded-l-lg bg-gray-700 hover:bg-gray-600 text-white transition flex items-center justify-center">
                                                            -
                                                        </button>
                                                        <input type="number" id="qty-{{ $item->id }}" name="quantite"
                                                            value="{{ $item->quantite }}" min="1"
                                                            class="w-16 h-8 bg-gray-700 border-y border-gray-600 text-center text-white focus:outline-none focus:border-purple-500"
                                                            onchange="this.form.submit()">
                                                        <button type="button"
                                                            onclick="incrementQty('qty-{{ $item->id }}')"
                                                            class="w-8 h-8 rounded-r-lg bg-gray-700 hover:bg-gray-600 text-white transition flex items-center justify-center">
                                                            +
                                                        </button>
                                                    </div>
                                                </form>

                                                {{-- Prix total ligne --}}
                                                <div class="text-right min-w-[100px]">
                                                    <div class="font-bold text-pink-400 text-lg">
                                                        {{ number_format($item->prix_unitaire * $item->quantite, 2, ',', ' ') }} €
                                                    </div>
                                                </div>

                                                {{-- Bouton suppression --}}
                                                <form method="POST" action="{{ route('cart.remove', $item->id) }}"
                                                    onsubmit="return confirm('Supprimer cet article ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="w-8 h-8 rounded-full bg-red-900/30 border border-red-500/50 text-red-400 hover:bg-red-900/50 hover:text-red-300 transition flex items-center justify-center"
                                                        title="Supprimer">
                                                        ×
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Vider le panier --}}
                                <div class="mt-6 pt-4 border-t border-gray-700">
                                    <form method="POST" action="{{ route('cart.clear') }}"
                                        onsubmit="return confirm('Êtes-vous sûr de vouloir vider le panier ?')">
                                        @csrf
                                        <button type="submit" class="text-red-400 hover:text-red-300 text-sm font-semibold transition">
                                            🗑️ Vider le panier
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Colonne latérale : Récapitulatif avec TVA --}}
                    <div class="lg:col-span-1">
                        <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700 overflow-hidden rounded-xl sticky top-4">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold bg-gradient-to-r from-purple-300 to-pink-300 bg-clip-text text-transparent mb-4">
                                    Récapitulatif
                                </h3>

                                <div class="space-y-3 mb-4">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-400">Articles</span>
                                        <span class="font-medium text-gray-200">{{ $cart->totalItems }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-400">Sous-total HT</span>
                                        <span class="font-medium text-gray-200">{{ number_format($cart->total, 2, ',', ' ') }} €</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-400">TVA (20%)</span>
                                        <span class="font-medium text-gray-200">{{ number_format($cart->tva_amount, 2, ',', ' ') }} €</span>
                                    </div>
                                </div>

                                <div class="border-t border-gray-700 pt-4 mb-6">
                                    <div class="flex justify-between text-xl font-bold">
                                        <span class="text-gray-200">Total TTC</span>
                                        <span class="bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">{{ number_format($cart->total_ttc, 2, ',', ' ') }} €</span>
                                    </div>
                                </div>

                                @if (empty($stockErrors))
                                    <a href="{{ route('orders.create') }}"
                                        class="block w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white text-center py-3 rounded-lg font-semibold transition">
                                        Valider ma commande
                                    </a>
                                @else
                                    <button disabled
                                        class="block w-full bg-gray-600 text-gray-300 text-center py-3 rounded-lg cursor-not-allowed font-semibold">
                                        Stock insuffisant
                                    </button>
                                @endif

                                @if ($cart->expires_at)
                                    <p class="text-xs text-gray-500 text-center mt-4">
                                        Votre panier expire dans {{ $cart->expires_at->diffForHumans() }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function incrementQty(inputId) {
        const input = document.getElementById(inputId);
        input.value = parseInt(input.value) + 1;
        input.form.submit();
    }

    function decrementQty(inputId) {
        const input = document.getElementById(inputId);
        const newValue = parseInt(input.value) - 1;
        if (newValue >= 1) {
            input.value = newValue;
            input.form.submit();
        }
    }
</script>
@endpush
