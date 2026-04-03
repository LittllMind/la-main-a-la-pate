<x-app-layout>
    <x-slot name="header">
        <h2>Détail de la vente du {{ $vente->date->format('d/m/Y') }}</h2>
    </x-slot>

    <div class="page-content">
        <div class="vente-details">
            <div class="vente-info">
                <p><strong>Date :</strong> {{ $vente->date->format('d/m/Y à H:i') }}</p>
                <p><strong>Mode de paiement :</strong> {{ ucfirst($vente->mode_paiement) }}</p>
                <p><strong>Total :</strong> <span class="text-lg">{{ number_format($vente->total, 2) }} €</span></p>
            </div>

            <h3>Articles vendus</h3>
            <div class="table-responsive">
                <table class="vinyle-table">
                    <thead>
                        <tr>
                            <th>Vinyle</th>
                            <th>Modèle</th>
                            <th>Prix unitaire</th>
                            <th>Quantité</th>
                            <th>Fond</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vente->lignes as $ligne)
                            <tr>
                                <td>{{ $ligne->vinyle->nom }}</td>
                                <td>{{ $ligne->vinyle->modele }}</td>
                                <td>{{ number_format($ligne->prix_unitaire, 2) }} €</td>
                                <td>{{ $ligne->quantite }}</td>
                                <td>{{ $ligne->fond ?? '-' }}</td>
                                <td><strong>{{ number_format($ligne->total, 2) }} €</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="form-actions"
                style="display: flex; justify-content: space-between; gap: 1rem; margin-top: 1.5rem;">
                {{-- Retour vers l’historique du bon jour --}}
                <a href="{{ route('ventes.index', ['date' => $vente->date->format('Y-m-d')]) }}"
                    class="btn btn-secondary">
                    ← Retour à l’historique
                </a>

                {{-- Annulation de la vente --}}
                <form method="POST" action="{{ route('ventes.destroy', $vente) }}"
                    onsubmit="return confirm('Annuler cette vente ? Les stocks seront restaurés et cette action est définitive.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        Annuler cette vente
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
