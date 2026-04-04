{{-- resources/views/marche/index.blade.php --}}
{{-- Interface Mode Marché - Optimisée mobile pour ventes sur place --}}

@extends('layouts.app')

@section('title', 'Mode Marché - Vente sur place')

@section('content')
<div x-data="modeMarche()" class="max-w-7xl mx-auto" @keydown.escape="showCart = false">
    
    {{-- Header avec stats du jour --}}
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-2xl p-4 mb-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold">🛒 Mode Marché</h1>
                <p class="text-purple-100 text-sm">Vente sur place - {{ now()->format('d/m/Y') }}</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold" x-text="formatPrice(stats.total_jour)">0 €</div>
                <div class="text-sm text-purple-100" x-text="stats.nb_ventes + ' ventes'">0 vente</div>
            </div>
        </div>
    </div>

    {{-- Barre d'actions --}}
    <div class="flex gap-2 mb-4 overflow-x-auto pb-2">
        <button @click="showCart = true" 
            class="flex items-center gap-2 bg-gray-800 hover:bg-gray-700 px-4 py-2 rounded-xl border border-gray-700 transition whitespace-nowrap"
            :class="cart.length > 0 ? 'border-purple-500 ring-2 ring-purple-500/20' : ''">
            🛒 Panier <span x-show="cart.length > 0" x-text="'(' + cartItemCount + ')'" class="bg-purple-600 px-2 py-0.5 rounded-full text-sm"></span>
        </button>
        <button @click="loadVentesJour()" class="flex items-center gap-2 bg-gray-800 hover:bg-gray-700 px-4 py-2 rounded-xl border border-gray-700 transition whitespace-nowrap">
            📊 Ventes du jour
        </button>
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 bg-gray-800 hover:bg-gray-700 px-4 py-2 rounded-xl border border-gray-700 transition whitespace-nowrap">
            🔧 Dashboard
        </a>
    </div>

    {{-- Recherche rapide --}}
    <div class="mb-4">
        <input type="text" x-model="search" placeholder="🔍 Rechercher un vinyle..."
            class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-gray-100 placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition"
            autofocus>
    </div>

    {{-- Grille de vinyles --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
        <template x-for="vinyle in filteredVinyles" :key="vinyle.id">
            <div @click="addToCart(vinyle)"
                class="bg-gray-800 rounded-xl overflow-hidden border border-gray-700 hover:border-purple-500 cursor-pointer transition-all active:scale-95"
                :class="isInCart(vinyle.id) ? 'ring-2 ring-purple-500' : ''">
                
                {{-- Image --}}
                <div class="aspect-square bg-gray-900 relative">
                    <img :src="vinyle.image_url || '/images/no-image.png'" :alt="vinyle.nom"
                        class="w-full h-full object-cover">
                    <div x-show="vinyle.quantite <= 3" x-cloak
                        class="absolute top-2 right-2 bg-red-600 text-white text-xs px-2 py-1 rounded-lg font-bold"
                        x-text="vinyle.quantite + ' restant'">
                    </div>
                </div>

                {{-- Info --}}
                <div class="p-3">
                    <h3 class="font-semibold text-sm text-gray-100 truncate" x-text="vinyle.nom"></h3>
                    <p class="text-xs text-gray-400 truncate" x-text="vinyle.artiste_principale"></p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-lg font-bold text-purple-400" x-text="formatPrice(vinyle.prix)"></span>
                        <span x-show="isInCart(vinyle.id)" class="text-green-400 text-xl">✓</span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Message si vide --}}
    <div x-show="filteredVinyles.length === 0" x-cloak class="text-center py-12">
        <div class="text-4xl mb-2">🔍</div>
        <p class="text-gray-400">Aucun vinyle trouvé</p>
    </div>

    {{-- Drawer Panier (slide from right) --}}
    <div x-show="showCart" x-cloak 
        class="fixed inset-0 z-50"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        
        {{-- Backdrop --}}
        <div @click="showCart = false" class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
        
        {{-- Drawer --}}
        <div class="absolute right-0 top-0 bottom-0 w-full max-w-md bg-gray-900 border-l border-gray-700 flex flex-col"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full">
            
            {{-- Header --}}
            <div class="p-4 border-b border-gray-700 flex items-center justify-between bg-gray-800">
                <h2 class="text-xl font-bold">🛒 Panier</h2>
                <button @click="showCart = false" class="text-gray-400 hover:text-white text-2xl">&times;</button>
            </div>

            {{-- Items --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                <template x-for="(item, index) in cart" :key="index">
                    <div class="bg-gray-800 rounded-xl p-3 border border-gray-700">
                        <div class="flex gap-3">
                            <img :src="item.image_url || '/images/no-image.png'" class="w-16 h-16 object-cover rounded-lg bg-gray-900">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-sm truncate" x-text="item.nom"></h4>
                                <p class="text-xs text-gray-400" x-text="item.artiste_principale"></p>
                                <div class="flex items-center gap-2 mt-2">
                                    <button @click="updateQuantity(index, -1)" class="w-8 h-8 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center">-</button>
                                    <span x-text="item.quantite" class="w-8 text-center font-bold"></span>
                                    <button @click="updateQuantity(index, 1)" class="w-8 h-8 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center">+</button>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-purple-400" x-text="formatPrice(item.prix * item.quantite)"></div>
                                <button @click="removeFromCart(index)" class="text-red-400 text-xs mt-1 hover:text-red-300">Supprimer</button>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Panier vide --}}
                <div x-show="cart.length === 0" class="text-center py-8 text-gray-500">
                    <div class="text-4xl mb-2">🛒</div>
                    <p>Panier vide</p>
                    <p class="text-sm">Cliquez sur un vinyle pour l'ajouter</p>
                </div>
            </div>

            {{-- Footer avec total et paiement --}}
            <div class="p-4 border-t border-gray-700 bg-gray-800 space-y-3">
                {{-- Réduction --}}
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-400">Réduction:</span>
                    <input type="number" x-model.number="reduction" min="0" step="0.5"
                        class="w-24 bg-gray-700 border border-gray-600 rounded-lg px-3 py-1 text-right">
                    <span class="text-gray-400">€</span>
                </div>

                {{-- Total --}}
                <div class="flex items-center justify-between text-xl">
                    <span class="font-bold">Total:</span>
                    <span class="font-bold text-purple-400" x-text="formatPrice(cartTotal - reduction)"></span>
                </div>

                {{-- Modes de paiement --}}
                <div class="grid grid-cols-2 gap-2">
                    <button @click="modePaiement = 'cash'" 
                        :class="modePaiement === 'cash' ? 'bg-green-600 border-green-500' : 'bg-gray-700 border-gray-600'"
                        class="py-3 rounded-xl border-2 font-semibold transition">
                        💵 Espèces
                    </button>
                    <button @click="modePaiement = 'cb_terminal'"
                        :class="modePaiement === 'cb_terminal' ? 'bg-blue-600 border-blue-500' : 'bg-gray-700 border-gray-600'"
                        class="py-3 rounded-xl border-2 font-semibold transition">
                        💳 CB
                    </button>
                    <button @click="modePaiement = 'cheque'"
                        :class="modePaiement === 'cheque' ? 'bg-yellow-600 border-yellow-500' : 'bg-gray-700 border-gray-600'"
                        class="py-3 rounded-xl border-2 font-semibold transition">
                        📝 Chèque
                    </button>
                    <button @click="modePaiement = 'virement'"
                        :class="modePaiement === 'virement' ? 'bg-purple-600 border-purple-500' : 'bg-gray-700 border-gray-600'"
                        class="py-3 rounded-xl border-2 font-semibold transition">
                        🏦 Virement
                    </button>
                </div>

                {{-- Notes vendeur --}}
                <input type="text" x-model="notesVendeur" placeholder="Notes (optionnel)..."
                    class="w-full bg-gray-700 border border-gray-600 rounded-xl px-4 py-2 text-sm">

                {{-- Identifiant client --}}
                <input type="text" x-model="affichageClient" placeholder="Nom client / Table / Ref..."
                    class="w-full bg-gray-700 border border-gray-600 rounded-xl px-4 py-2 text-sm">

                {{-- Bouton valider --}}
                <button @click="validerVente()" 
                    :disabled="cart.length === 0 || !modePaiement || loading"
                    :class="cart.length === 0 || !modePaiement ? 'bg-gray-700 cursor-not-allowed' : 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700'"
                    class="w-full py-4 rounded-xl font-bold text-lg transition flex items-center justify-center gap-2">
                    <span x-show="loading" class="animate-spin">⏳</span>
                    <span x-text="loading ? 'Validation...' : '✓ Valider la vente'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Ventes du jour --}}
    <div x-show="showVentesJour" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
        @keydown.escape="showVentesJour = false">
        <div class="bg-gray-900 rounded-2xl max-w-lg w-full max-h-[80vh] flex flex-col border border-gray-700">
            <div class="p-4 border-b border-gray-700 flex items-center justify-between">
                <h2 class="text-xl font-bold">📊 Ventes du jour</h2>
                <button @click="showVentesJour = false" class="text-gray-400 hover:text-white text-2xl">&times;</button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <div class="space-y-2">
                    <template x-for="vente in ventesJour" :key="vente.id">
                        <div class="bg-gray-800 rounded-xl p-3 flex items-center justify-between">
                            <div>
                                <div class="font-semibold" x-text="vente.numero"></div>
                                <div class="text-sm text-gray-400">
                                    <span x-text="vente.heure"></span> - 
                                    <span x-text="vente.client || 'Anonyme'"></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-purple-400" x-text="formatPrice(vente.total)"></div>
                                <div class="text-xs text-gray-500" x-text="vente.mode_paiement"></div>
                            </div>
                        </div>
                    </template>
                    <div x-show="ventesJour.length === 0" class="text-center py-8 text-gray-500">
                        Aucune vente aujourd'hui
                    </div>
                </div>
            </div>
            <div class="p-4 border-t border-gray-700 bg-gray-800">
                <div class="flex justify-between items-center text-xl font-bold">
                    <span>Total journée:</span>
                    <span class="text-purple-400" x-text="formatPrice(stats.total_jour)"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Notification succès --}}
    <div x-show="showSuccess" x-cloak 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-96 bg-green-600 text-white p-4 rounded-xl shadow-lg z-50">
        <div class="flex items-center gap-3">
            <span class="text-2xl">✓</span>
            <div>
                <div class="font-bold">Vente enregistrée !</div>
                <div class="text-sm" x-text="'Commande ' + lastOrder.numero"></div>
                <div class="text-lg font-bold" x-text="formatPrice(lastOrder.total)"></div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function modeMarche() {
        return {
            // Data
            vinyles: @json($vinyles),
            search: '',
            cart: [],
            showCart: false,
            modePaiement: 'cash',
            reduction: 0,
            notesVendeur: '',
            affichageClient: '',
            loading: false,
            showSuccess: false,
            showVentesJour: false,
            ventesJour: [],
            stats: { total_jour: 0, nb_ventes: 0 },
            lastOrder: {},

            // Computed
            get filteredVinyles() {
                const s = this.search.toLowerCase().trim();
                if (!s) return this.vinyles;
                return this.vinyles.filter(v => 
                    v.nom.toLowerCase().includes(s) || 
                    v.artiste_principale.toLowerCase().includes(s)
                );
            },

            get cartItemCount() {
                return this.cart.reduce((sum, item) => sum + item.quantite, 0);
            },

            get cartTotal() {
                return this.cart.reduce((sum, item) => sum + (item.prix * item.quantite), 0);
            },

            // Methods
            addToCart(vinyle) {
                const existing = this.cart.find(item => item.id === vinyle.id);
                if (existing) {
                    if (existing.quantite < vinyle.quantite) {
                        existing.quantite++;
                    }
                } else {
                    this.cart.push({
                        id: vinyle.id,
                        nom: vinyle.nom,
                        artiste_principale: vinyle.artiste_principale,
                        prix: vinyle.prix,
                        quantite: 1,
                        image_url: vinyle.image_url,
                        stock: vinyle.quantite
                    });
                }
                this.showCart = true;
            },

            removeFromCart(index) {
                this.cart.splice(index, 1);
            },

            updateQuantity(index, delta) {
                const item = this.cart[index];
                const newQty = item.quantite + delta;
                if (newQty >= 1 && newQty <= item.stock) {
                    item.quantite = newQty;
                }
            },

            isInCart(vinyleId) {
                return this.cart.some(item => item.id === vinyleId);
            },

            formatPrice(amount) {
                return new Intl.NumberFormat('fr-FR', {
                    style: 'currency',
                    currency: 'EUR'
                }).format(amount || 0);
            },

            async validerVente() {
                if (this.cart.length === 0 || !this.modePaiement) return;
                
                this.loading = true;
                
                try {
                    const response = await fetch('/marche/store', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"')?.content || '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            items: this.cart.map(item => ({
                                vinyle_id: item.id,
                                quantite: item.quantite
                            })),
                            mode_paiement: this.modePaiement,
                            reduction: parseFloat(this.reduction) || 0,
                            notes_vendeur: this.notesVendeur,
                            affichage_client: this.affichageClient
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.lastOrder = data;
                        this.showSuccess = true;
                        this.showCart = false;
                        this.cart = [];
                        this.reduction = 0;
                        this.notesVendeur = '';
                        this.affichageClient = '';
                        this.loadStats();
                        
                        setTimeout(() => this.showSuccess = false, 3000);
                    } else {
                        alert(data.message || 'Erreur lors de la vente');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Erreur de connexion');
                } finally {
                    this.loading = false;
                }
            },

            async loadVentesJour() {
                try {
                    const response = await fetch('/marche/ventes-jour');
                    const data = await response.json();
                    this.ventesJour = data.ventes;
                    this.stats = { total_jour: data.total_jour, nb_ventes: data.nb_ventes };
                    this.showVentesJour = true;
                } catch (error) {
                    console.error('Erreur chargement ventes:', error);
                }
            },

            async loadStats() {
                try {
                    const response = await fetch('/marche/ventes-jour');
                    const data = await response.json();
                    this.stats = { total_jour: data.total_jour, nb_ventes: data.nb_ventes };
                } catch (error) {
                    console.error('Erreur stats:', error);
                }
            },

            init() {
                this.loadStats();
                // Rafraîchir stats toutes les 30 secondes
                setInterval(() => this.loadStats(), 30000);
            }
        }
    }
</script>
@endpush

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush
