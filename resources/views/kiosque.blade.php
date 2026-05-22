{{-- resources/views/kiosque.blade.php --}}

@php
    /** @var \App\Services\CartService $cartService */
    $cartService = app(\App\Services\CartService::class);
    $cart = $cartService->getCart();
    $cartCount = $cart->items->sum('quantite');
@endphp

@extends('layouts.kiosque')

@section('title', 'Catalogue - Vinyle Hydrodécoupé')

@section('content')
{{-- DEBUG: Vérification données --}}
@if(empty($vinylesData))
    <div class="bg-red-900/50 border border-red-500 p-6 rounded-xl mb-6">
        <h2 class="text-xl font-bold text-red-400 mb-2">⚠️ Aucun vinyle à afficher</h2>
        <p class="text-gray-300">La variable \$vinylesData est vide.</p>
        <p class="text-sm text-gray-400 mt-2">Vérifiez qu'il y a des vinyles en base de données.</p>
    </div>
@else
    <!-- {{ count($vinylesData) }} vinyles chargés -->
@endif

<div x-data="kiosqueComponent(@js($vinylesData))" class="space-y-6">
    <!-- Header avec titre et panier -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                🎵 Catalogue Vinyles
            </h1>
            <p class="text-gray-400 mt-1">Découvrez notre collection exclusive</p>
        </div>
        <a href="{{ route('cart.index') }}" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 px-6 py-3 rounded-2xl font-semibold transition flex items-center justify-center gap-2">
            🛒 Mon Panier <span class="bg-white/20 px-2 py-0.5 rounded-full text-sm">{{ $cartCount }}</span>
        </a>
    </div>

    <!-- Barre de recherche et filtres -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="w-full sm:max-w-md">
            <div class="relative">
                <input type="text" x-model="search" placeholder="🔍 Rechercher par nom ou modèle..."
                    class="w-full bg-gray-800 border border-gray-700 rounded-2xl px-4 py-3 text-gray-100 placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition" />
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="showAll = !showAll"
                class="px-4 py-2 rounded-xl transition border border-gray-700 hover:border-purple-500 hover:bg-purple-500/10"
                :class="showAll ? 'bg-purple-500/20 border-purple-500 text-purple-400' : 'bg-gray-800 text-gray-400'">
                <span x-text="showAll ? 'Masquer rupture de stock' : 'Afficher tous'"></span>
            </button>
        </div>
    </div>

    <!-- Grille de vinyles -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <template x-for="vinyle in filteredVinyles" :key="vinyle.id">
            <div class="bg-gray-800 rounded-2xl overflow-hidden border border-gray-700 hover:border-purple-500/50 hover:shadow-lg hover:shadow-purple-500/10 transition-all duration-300 group">
                <!-- Image -->
                <div class="w-full h-56 bg-gray-900 relative overflow-hidden">
                    <img :src="vinyle.image || '/images/no-image.png'" :alt="vinyle.artiste"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    <div x-show="(vinyle.quantite ?? 0) <= 0" x-cloak
                        class="absolute inset-0 bg-black/60 flex items-center justify-center">
                        <span class="bg-red-600 text-white px-4 py-2 rounded-xl font-semibold">Rupture de stock</span>
                    </div>
                </div>

                <!-- Contenu -->
                <div class="p-4 space-y-3">
                    <div>
                        <h3 class="font-bold text-lg text-gray-100 truncate" x-text="vinyle.artiste"></h3>
                        <p class="text-sm text-gray-400" x-text="vinyle.modele"></p>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="text-2xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                            <span>À partir de </span><span x-text="formatPrice(vinyle.prix)"></span>
                        </div>
                        <div class="text-sm text-gray-500" x-text="`Stock: ${vinyle.quantite ?? 0}`"></div>
                    </div>

                    <button type="button"
                        @click.stop="openQuantityModal(vinyle)"
                        :disabled="(vinyle.quantite ?? 0) <= 0"
                        class="w-full py-3 rounded-xl font-semibold transition"
                        :class="(vinyle.quantite ?? 0) > 0
                            ? 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white'
                            : 'bg-gray-700 text-gray-500 cursor-not-allowed'">
                        <span x-show="(vinyle.quantite ?? 0) > 0">Ajouter au panier</span>
                        <span x-show="(vinyle.quantite ?? 0) <= 0">Indisponible</span>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Message si aucun résultat -->
    <div x-show="filteredVinyles.length === 0" x-cloak class="text-center py-12">
        <div class="text-6xl mb-4">🔍</div>
        <h3 class="text-xl font-semibold text-gray-400">Aucun vinyle trouvé</h3>
        <p class="text-gray-500 mt-2">Essayez une autre recherche</p>
    </div>

    <!-- Bouton panier mobile flottant -->
    <div class="fixed inset-x-4 bottom-4 sm:hidden">
        <a href="{{ route('cart.index') }}"
            class="block w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-4 rounded-2xl font-semibold text-center shadow-lg">
            🛒 Voir mon panier ({{ $cartCount }})
        </a>
    </div>

    <!-- Modal de sélection de quantité -->
    <div x-show="selectedVinyle !== null" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
        @click.self="closeQuantityModal()"
        @keydown.escape.window="closeQuantityModal()"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div class="bg-gray-800 rounded-2xl p-6 max-w-md w-full border border-gray-700 shadow-xl"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            <h3 class="text-xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent"
                x-text="selectedVinyle?.nom"></h3>

            <!-- Image -->
            <div class="my-4 rounded-xl overflow-hidden bg-gray-900">
                <img :src="selectedVinyle?.image || '/images/no-image.png'" :alt="selectedVinyle?.nom"
                    class="w-full h-56 object-contain" />
            </div>

            <p class="text-sm text-gray-400" x-text="selectedVinyle?.modele"></p>

            <!-- Sélection quantité -->
            <div class="flex items-center justify-center gap-4 my-4">
                <button @click="decrementQuantity()"
                    class="w-12 h-12 rounded-xl bg-gray-700 hover:bg-gray-600 flex items-center justify-center text-xl font-bold transition">-</button>
                <div class="text-3xl font-bold text-gray-100 w-16 text-center" x-text="selectedQuantity"></div>
                <button @click="incrementQuantity()"
                    class="w-12 h-12 rounded-xl bg-gray-700 hover:bg-gray-600 flex items-center justify-center text-xl font-bold transition">+</button>
            </div>

            <!-- Sélection fond -->
            <div class="my-4">
                <label for="fond" class="block text-sm font-semibold text-gray-300 mb-2">Fond</label>
                <select id="fond" x-model="selectedFond"
                    class="w-full bg-gray-700 border border-gray-600 rounded-xl px-4 py-3 text-gray-100 focus:outline-none focus:border-purple-500">
                    <option value="standard">Standard (sans supplément)</option>
                    <option value="miroir">Fond miroir (+8 €)</option>
                    <option value="dore">Fond doré (+13 €)</option>
                </select>
            </div>

            <!-- Prix total -->
            <div class="text-center py-3 rounded-xl bg-gray-900 border border-gray-700">
                <span class="text-sm text-gray-400">Prix unitaire</span>
                <div class="text-2xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent"
                    x-text="formatPrice(currentUnitPrice())"></div>
            </div>

            <!-- Boutons -->
            <div class="flex gap-3 mt-6">
                <button @click="closeQuantityModal()"
                    class="flex-1 py-3 rounded-xl bg-gray-700 hover:bg-gray-600 text-gray-300 font-semibold transition">
                    Annuler
                </button>
                <button @click="submitCart()"
                    class="flex-1 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold transition">
                    Ajouter
                </button>
            </div>

            <!-- Formulaire caché -->
            <form x-ref="addToCartForm" action="{{ route('cart.add') }}" method="POST" class="hidden">
                @csrf
                <input type="hidden" name="vinyle_id" x-ref="vinyleId">
                <input type="hidden" name="quantite" x-ref="quantite">
                <input type="hidden" name="fond" x-ref="fond">
            </form>
        </div>
    </div>
</div>
@endsection

@push('head')
    {{-- Schema.org ItemList + Products pour le catalogue --}}
    @include('components.schema-org', ['type' => 'catalog', 'items' => $vinylesData ?? []])
@endpush

@push('scripts')
<script>
    function kiosqueComponent(vinylesData) {
        return {
            vinyles: vinylesData,
            search: '',
            showAll: false,

            selectedVinyle: null,
            selectedQuantity: 1,
            selectedFond: 'standard',

            get filteredVinyles() {
                const s = (this.search || '').toLowerCase().trim();
                return this.vinyles.filter(v => {
                    const artiste = (v.artiste || '').toLowerCase();
                    const modele = (v.modele || '').toLowerCase();
                    const matchesSearch = !s || artiste.includes(s) || modele.includes(s);
                    const inStock = this.showAll || (v.quantite ?? 0) > 0;
                    return matchesSearch && inStock;
                });
            },

            openQuantityModal(vinyle) {
                if ((vinyle.quantite ?? 0) <= 0) return;
                this.selectedVinyle = vinyle;
                this.selectedQuantity = 1;
                this.selectedFond = 'standard';
                // Bloquer le scroll du body
                document.body.style.overflow = 'hidden';
            },

            closeQuantityModal() {
                this.selectedVinyle = null;
                this.selectedQuantity = 1;
                this.selectedFond = 'standard';
                // Réactiver le scroll du body
                document.body.style.overflow = '';
            },

            incrementQuantity() {
                if (!this.selectedVinyle) return;
                const max = (this.selectedVinyle.quantite ?? 0);
                if (this.selectedQuantity < max) this.selectedQuantity++;
            },

            decrementQuantity() {
                if (this.selectedQuantity > 1) this.selectedQuantity--;
            },

            currentUnitPrice() {
                if (!this.selectedVinyle) return 0;
                const base = Number(this.selectedVinyle.prix) || 0;
                const supplement = this.selectedFond === 'miroir' ? 8 : (this.selectedFond === 'dore' ? 13 : 0);
                return base + supplement;
            },

            formatPrice(amount) {
                const num = Number(amount) || 0;
                return new Intl.NumberFormat('fr-FR', {
                    style: 'currency',
                    currency: 'EUR'
                }).format(num);
            },

            submitCart() {
                if (!this.selectedVinyle) return;

                this.$refs.vinyleId.value = this.selectedVinyle.id;
                this.$refs.quantite.value = this.selectedQuantity;
                this.$refs.fond.value = this.selectedFond;

                this.$refs.addToCartForm.submit();
            },
        }
    }
</script>
@endpush