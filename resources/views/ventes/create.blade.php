<x-app-layout>
    <x-slot name="header">
        <h2>Nouvelle Vente</h2>
    </x-slot>



    <div class="page-content" x-data="venteForm()">
        <form @submit.prevent="submitForm" class="form-container">
            @csrf

            {{-- Ligne date + mode de paiement --}}
            <div class="form-row-top">
                <div class="form-group">
                    <label for="date">Date *</label>
                    <input type="date" id="date" x-model="formData.date" required class="form-input">
                </div>

                <div class="form-group">
                    <label for="mode_paiement">Mode de paiement *</label>
                    <select id="mode_paiement" x-model="formData.mode_paiement" required class="form-input">
                        <option value="">Sélectionner...</option>
                        <option value="especes">Espèces</option>
                        <option value="carte">Carte bancaire</option>
                        <option value="cheque">Chèque</option>
                    </select>
                </div>
            </div>

            {{-- Articles --}}
            <h3>Articles à vendre</h3>

            <div class="card-articles">
                {{-- En-tête desktop --}}
                <div class="vente-items-header">
                    <div>Vinyle</div>
                    <div>Qté</div>
                    <div>Fond</div>
                    <div>PU</div>
                    <div></div>
                </div>

                {{-- Lignes d’articles --}}
                <template x-for="(item, index) in items" :key="index">
                    <div class="vente-item">
                        <div class="vente-item-row">
                            {{-- Vinyle --}}
                            <div class="form-group col-vinyle">
                                <label class="field-label-mobile">Vinyle *</label>
                                <select x-model="item.id" @change="updateItemPrice(index, $event)" required
                                    class="form-input form-input-compact">
                                    <option value="">Sélectionner un vinyle...</option>
                                    @foreach ($vinyles as $vinyle)
                                        <option value="{{ $vinyle->id }}" data-prix="{{ $vinyle->prix }}"
                                            data-stock="{{ $vinyle->quantite }}">
                                            {{ $vinyle->nom }} - {{ $vinyle->modele }}
                                            (Stock: {{ $vinyle->quantite }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Quantité --}}

                            <div class="form-group col-quantite">
                                <label class="field-label-mobile">Quantité *</label>
                                <input type="number" x-model="item.quantite" min="1" :max="item.stock"
                                    @input="calculateTotal" required class="form-input form-input-compact text-center">
                            </div>

                            {{-- Fond --}}
                            <div class="form-group col-fond">
                                <label class="field-label-mobile">Fond</label>
                                <select x-model="item.fond" @change="updateFond(index)"
                                    class="form-input form-input-compact">
                                    <option value="standard">Standard (par défaut)</option>
                                    <option value="miroir">Fond miroir (+8 €)</option>
                                    <option value="dore">Fond doré (+13 €)</option>
                                </select>
                            </div>

                            {{-- Prix unitaire (base + surcoût fond) --}}
                            <div class="form-group col-prix">
                                <label class="field-label-mobile">Prix unitaire</label>
                                <input type="text" :value="formatPrice(item.prix)" readonly
                                    class="form-input form-input-compact text-right">
                            </div>

                            {{-- Bouton supprimer --}}
                            <div class="form-group col-delete">
                                <button type="button" @click="removeItem(index)" class="btn btn-danger btn-sm"
                                    :disabled="items.length === 1">
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <button type="button" @click="addItem" class="btn btn-secondary" style="margin-top: 0.75rem;">
                    + Ajouter un article
                </button>
            </div>

            {{-- Total --}}
            <div class="vente-total">
                Total : <span x-text="formatPrice(total)" class="ml-1"></span> €
            </div>

            {{-- Actions --}}
            <div class="form-actions">
                <a href="{{ route('ventes.index') }}" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">Enregistrer la vente</button>
            </div>
        </form>
    </div>

    <script>
        function venteForm() {
            return {
                formData: {
                    date: new Date().toISOString().split('T')[0],
                    mode_paiement: 'carte',
                },

                // Surcoûts selon le fond
                fondSupplements: {
                    standard: 0,
                    miroir: 8,
                    dore: 13,
                },

                items: [{
                    id: '',
                    quantite: 1,
                    fond: 'standard',
                    basePrix: 0, // prix du vinyle seul
                    prix: 0, // prix unitaire final (base + fond)
                    stock: 0
                }],

                total: 0,

                addItem() {
                    this.items.push({
                        id: '',
                        quantite: 1,
                        fond: 'standard',
                        basePrix: 0,
                        prix: 0,
                        stock: 0
                    });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                        this.calculateTotal();
                    }
                },

                // Quand on change le vinyle
                updateItemPrice(index, event) {
                    const select = event.target;
                    const option = select.options[select.selectedIndex];

                    if (option && option.value) {
                        const base = parseFloat(option.dataset.prix || 0);
                        this.items[index].basePrix = base;
                        this.items[index].stock = parseInt(option.dataset.stock || 0);

                        this.recalcItem(index);
                        this.calculateTotal();
                    }
                },

                // Quand on change le fond
                updateFond(index) {
                    this.recalcItem(index);
                    this.calculateTotal();
                },

                // Recalcule le prix unitaire pour 1 article (base + surcoût fond)
                recalcItem(index) {
                    const item = this.items[index];
                    const base = parseFloat(item.basePrix || 0);
                    const extra = this.fondSupplements[item.fond] || 0;

                    item.prix = base + extra;
                },

                // Recalcule le total de la vente
                calculateTotal() {
                    this.total = this.items.reduce((sum, item) => {
                        const qte = parseFloat(item.quantite || 0);
                        const prix = parseFloat(item.prix || 0);
                        return sum + (prix * qte);
                    }, 0);
                },

                formatPrice(price) {
                    return parseFloat(price || 0).toFixed(2);
                },

                async submitForm() {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('date', this.formData.date);
                    formData.append('mode_paiement', this.formData.mode_paiement);

                    this.items.forEach((item, index) => {
                        formData.append(`vinyles[${index}][id]`, item.id);
                        formData.append(`vinyles[${index}][quantite]`, item.quantite);
                        formData.append(`vinyles[${index}][fond]`, item.fond);
                    });


                    try {
                        const response = await fetch('{{ route('ventes.store') }}', {
                            method: 'POST',
                            body: formData,
                        });

                        if (response.ok) {
                            window.location.href = '{{ route('ventes.index') }}';
                        } else {
                            alert('Erreur lors de l\'enregistrement');
                        }
                    } catch (error) {
                        console.error(error);
                        alert('Erreur réseau');
                    }
                }
            }
        }
    </script>
</x-app-layout>
