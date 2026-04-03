<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mode Kiosque - Stock Vinyles</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="kiosque-container" x-data="kioskApp()">
        <div class="kiosque-header">
            <h1 style="margin: 0 0 15px 0; font-size: 28px;">🎵 Catalogue Vinyles</h1>

            <input type="text" x-model="search" placeholder="Rechercher par nom ou modèle..." class="kiosque-search">

            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button @click="showAll = !showAll" class="btn btn-secondary">
                    <span x-text="showAll ? 'Masquer rupture de stock' : 'Afficher tous'"></span>
                </button>
            </div>
        </div>

        <!-- Grille des vinyles -->
        <div class="kiosque-grid">
            <template x-for="vinyle in filteredVinyles" :key="vinyle.id">
                <div class="kiosque-card" :class="{ 'selected': isInCart(vinyle.id) }" @click="toggleVinyle(vinyle)">

                    <!-- Image de la carte : on affiche l'image standard si dispo -->
                    <img :src="vinyle.image_standard || '/images/no-image.png'" :alt="vinyle.nom"
                        class="kiosque-image">

                    <div class="kiosque-content">
                        <h3 class="kiosque-title" x-text="vinyle.nom"></h3>
                        <p class="kiosque-subtitle" x-text="vinyle.modele"></p>

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="kiosque-price" x-text="formatPrice(vinyle.prix)"></span>
                            <span class="kiosque-stock" x-text="`Stock: ${vinyle.quantite}`"></span>
                        </div>

                        <template x-if="isInCart(vinyle.id)">
                            <div
                                style="margin-top: 10px; padding: 8px; background: #4F46E5; color: white; border-radius: 4px; text-align: center; font-weight: bold;">
                                <span x-text="`${getCartItem(vinyle.id).quantite} dans le panier`"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- Panier fixé en bas -->
        <div class="kiosque-cart" x-show="cart.length > 0" x-transition>
            <div class="cart-content">
                <div class="cart-items">
                    <strong>Votre sélection :</strong>
                    <span x-text="cart.length"></span> article(s)
                </div>
                <div class="cart-total">
                    Total estimé : <span x-text="formatPrice(cartTotal)"></span>
                </div>
                <div class="cart-buttons">
                    <button @click="clearCart()" class="btn-large btn-clear">
                        🗑️ Effacer
                    </button>
                    <button @click="openCheckout()" class="btn-large btn-sell">
                        ✅ Valider ma sélection
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de sélection de quantité + image dynamique -->
        <div class="modal" :class="{ 'show': showQuantityModal }">
            <div class="modal-content">
                <h2 style="margin-top: 0;">Ajouter au panier</h2>

                <template x-if="selectedVinyle">
                    <div>

                        <!-- Aperçu de l'image selon le fond sélectionné -->
                        <div style="text-align: center; margin-bottom: 15px;">
                            <img :src="currentImageUrl()" :alt="selectedVinyle.nom" class="kiosque-image"
                                style="max-width: 260px; max-height: 260px; object-fit: contain;">
                        </div>

                        <h3 x-text="selectedVinyle.nom"></h3>
                        <p x-text="selectedVinyle.modele"></p>

                        <!-- Sélecteur de quantité -->
                        <div class="quantity-selector">
                            <button @click="decrementQuantity()" class="quantity-btn">-</button>
                            <span class="quantity-value" x-text="selectedQuantity"></span>
                            <button @click="incrementQuantity()" class="quantity-btn">+</button>
                        </div>

                        <!-- Choix du fond -->
                        <div class="form-group" style="margin-top: 15px;">
                            <label for="fond">Fond</label>
                            <select id="fond" x-model="selectedFond" class="form-input">
                                <option value="standard">Standard (sans supplément)</option>
                                <option value="miroir">Fond miroir (+8 €)</option>
                                <option value="dore">Fond doré (+13 €)</option>
                            </select>
                        </div>

                        <!-- Prix unitaire dynamique -->
                        <div class="form-group" style="margin-top: 15px;">
                            <label>Prix unitaire</label>
                            <div style="font-size: 1.3rem; font-weight: bold;">
                                <span x-text="formatPrice(currentUnitPrice())"></span>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="modal-actions"
                            style="margin-top: 25px; display: flex; justify-content: flex-end; gap: 10px;">
                            <button @click="closeQuantityModal()" class="btn btn-secondary">
                                Annuler
                            </button>
                            <button @click="addToCart()" class="btn btn-primary">
                                Ajouter
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Modal de paiement / confirmation -->
        <div class="modal" :class="{ 'show': showCheckoutModal }">
            <div class="modal-content">
                <h2 style="margin-top: 0;">Valider votre commande</h2>

                <div class="checkout-summary">
                    <h3>Récapitulatif</h3>
                    <template x-for="item in cart" :key="item.id + '-' + item.fond">
                        <div class="checkout-item">
                            <div>
                                <span x-text="`${item.nom} (${item.modele})`"></span><br>
                                <small x-text="`Fond : ${item.fond}`"></small>
                            </div>
                            <span x-text="`${item.quantite} x ${formatPrice(item.prixUnitaire)}`"></span>
                            <strong x-text="formatPrice(item.quantite * item.prixUnitaire)"></strong>
                        </div>
                    </template>
                    <div class="checkout-total">
                        <strong>Total :</strong>
                        <strong x-text="formatPrice(cartTotal)"></strong>
                    </div>
                </div>

                <p style="margin-top: 1rem;">
                    Le paiement sera effectué en carte bancaire (simulation pour le moment).
                </p>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button @click="closeCheckout()" class="btn btn-secondary" style="flex: 1;">
                        Annuler
                    </button>
                    <button @click="confirmSale()" class="btn btn-primary" style="flex: 1;" :disabled="isSubmitting">
                        <span x-show="!isSubmitting">Confirmer la commande</span>
                        <span x-show="isSubmitting">Enregistrement...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation -->
        <div class="modal" :class="{ 'show': showSuccessModal }">
            <div class="modal-content" style="text-align: center;">
                <div style="font-size: 64px; margin-bottom: 20px;">✅</div>
                <h2>Commande confirmée !</h2>
                <p style="font-size: 24px; color: #10B981; font-weight: bold;" x-text="formatPrice(lastSaleTotal)">
                </p>
                <button @click="closeSuccess()" class="btn btn-primary btn-large" style="margin-top: 20px;">
                    Retour au catalogue
                </button>
            </div>
        </div>
    </div>

    <script>
        function kioskApp() {
            return {
                // vinyles doit contenir pour chaque élément :
                // id, nom, modele, prix, quantite,
                // image_standard, image_miroir, image_dore (URLs ou null)
                vinyles: @json($vinylesData),

                search: '',
                showAll: false,

                cart: [],
                selectedVinyle: null,
                selectedQuantity: 1,
                selectedFond: 'standard',

                showQuantityModal: false,
                showCheckoutModal: false,
                showSuccessModal: false,

                modePaiement: 'carte',
                isSubmitting: false,
                lastSaleTotal: 0,

                // Suppléments selon le type de fond
                fondSupplements: {
                    standard: 0,
                    miroir: 8,
                    dore: 13,
                },

                // --- GETTERS ---

                get filteredVinyles() {
                    return this.vinyles.filter(v => {
                        const search = this.search.toLowerCase();
                        const matchesSearch =
                            (v.nom || '').toLowerCase().includes(search) ||
                            (v.modele || '').toLowerCase().includes(search);

                        const inStock = this.showAll || (v.quantite ?? 0) > 0;
                        return matchesSearch && inStock;
                    });
                },

                get cartTotal() {
                    return this.cart.reduce((sum, item) => {
                        return sum + item.prixUnitaire * item.quantite;
                    }, 0);
                },

                // Prix unitaire courant selon le fond sélectionné
                currentUnitPrice() {
                    if (!this.selectedVinyle) return 0;

                    const base = Number(this.selectedVinyle.prix || 0);
                    const supplement = Number(this.fondSupplements[this.selectedFond] || 0);

                    return base + supplement;
                },

                // URL de l'image en fonction du fond sélectionné
                currentImageUrl() {
                    if (!this.selectedVinyle) {
                        return '/images/no-image.png';
                    }

                    const fallback = this.selectedVinyle.image_standard || '/images/no-image.png';

                    if (this.selectedFond === 'miroir') {
                        return this.selectedVinyle.image_miroir || fallback;
                    }

                    if (this.selectedFond === 'dore') {
                        return this.selectedVinyle.image_dore || fallback;
                    }

                    return fallback;
                },

                // --- UTILITAIRES ---

                formatPrice(value) {
                    return (Number(value ?? 0)).toFixed(2).replace('.', ',') + ' €';
                },

                getQuantityInCart(vinyleId) {
                    return this.cart
                        .filter(i => i.id === vinyleId)
                        .reduce((sum, i) => sum + i.quantite, 0);
                },

                isInCart(vinyleId) {
                    return this.getQuantityInCart(vinyleId) > 0;
                },

                getCartItem(vinyleId) {
                    return {
                        quantite: this.getQuantityInCart(vinyleId),
                    };
                },

                clearCart() {
                    this.cart = [];
                },

                // --- SÉLECTION DE VINYLE ---

                toggleVinyle(vinyle) {
                    if ((vinyle.quantite ?? 0) <= 0) {
                        alert('Ce vinyle est en rupture de stock');
                        return;
                    }

                    this.selectedVinyle = vinyle;
                    this.selectedQuantity = 1;
                    this.selectedFond = 'standard';
                    this.showQuantityModal = true;
                },

                incrementQuantity() {
                    if (!this.selectedVinyle) return;

                    const dejaDansPanier = this.getQuantityInCart(this.selectedVinyle.id);
                    const maxPossible = (this.selectedVinyle.quantite ?? 0) - dejaDansPanier;

                    if (this.selectedQuantity < maxPossible) {
                        this.selectedQuantity++;
                    }
                },

                decrementQuantity() {
                    if (this.selectedQuantity > 1) {
                        this.selectedQuantity--;
                    }
                },

                closeQuantityModal() {
                    this.showQuantityModal = false;
                    this.selectedVinyle = null;
                    this.selectedQuantity = 1;
                    this.selectedFond = 'standard';
                },

                // --- PANIER ---

                addToCart() {
                    if (!this.selectedVinyle) return;

                    if (!this.selectedFond) {
                        alert('Merci de sélectionner un type de fond');
                        return;
                    }

                    const base = Number(this.selectedVinyle.prix || 0);
                    const supplement = Number(this.fondSupplements[this.selectedFond] || 0);
                    const prixUnitaire = base + supplement;

                    const existingItem = this.cart.find(
                        item => item.id === this.selectedVinyle.id && item.fond === this.selectedFond
                    );

                    if (existingItem) {
                        existingItem.quantite += this.selectedQuantity;
                    } else {
                        this.cart.push({
                            id: this.selectedVinyle.id,
                            nom: this.selectedVinyle.nom,
                            modele: this.selectedVinyle.modele,
                            fond: this.selectedFond,
                            quantite: this.selectedQuantity,
                            prixUnitaire: prixUnitaire,
                        });
                    }

                    this.closeQuantityModal();
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                },

                // --- CHECKOUT ---

                openCheckout() {
                    if (this.cart.length === 0) {
                        alert('Le panier est vide');
                        return;
                    }
                    this.showCheckoutModal = true;
                },

                closeCheckout() {
                    this.showCheckoutModal = false;
                },

                // --- CONFIRMATION DE COMMANDE ---

                async confirmSale() {
                    if (!this.cart.length) {
                        alert('Panier vide');
                        return;
                    }

                    const cartTotal = this.cartTotal;
                    this.isSubmitting = true;

                    try {
                        const response = await fetch('{{ route('kiosque.vendre') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                mode_paiement: 'carte',
                                vinyles: this.cart.map(item => ({
                                    id: item.id,
                                    quantite: item.quantite,
                                    fond: item.fond,
                                })),
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Erreur lors de l’enregistrement de la commande');
                        }

                        // Mise à jour des stocks en local
                        this.cart.forEach(item => {
                            const v = this.vinyles.find(v => v.id === item.id);
                            if (v) {
                                v.quantite = Math.max(0, (v.quantite ?? 0) - item.quantite);
                            }
                        });

                        this.lastSaleTotal = data.total ?? cartTotal;

                        this.clearCart();
                        this.showCheckoutModal = false;
                        this.showSuccessModal = true;
                    } catch (error) {
                        alert(error.message || 'Erreur inconnue');
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                closeSuccess() {
                    this.showSuccessModal = false;
                },
            };
        }
    </script>

</body>

</html>
