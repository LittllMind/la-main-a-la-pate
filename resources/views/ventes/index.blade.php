<x-app-layout>
    <x-slot name="header">
        <div class="header-actions"
            style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
            <div>
                <h2 style="margin: 0;">
                    Ventes du {{ $currentDate->format('d/m/Y') }}
                </h2>
                <div style="font-size: 0.9rem; color: #6b7280;">
                    @if ($previousDate)
                        <a href="{{ route('ventes.index', ['date' => $previousDate]) }}">⟵ Jour précédent</a>
                    @else
                        ⟵ Jour précédent
                    @endif
                    |
                    @if ($nextDate)
                        <a href="{{ route('ventes.index', ['date' => $nextDate]) }}">Jour suivant ⟶</a>
                    @else
                        Jour suivant ⟶
                    @endif
                </div>
            </div>

            <a href="{{ route('ventes.create') }}" class="btn btn-primary">
                + Nouvelle vente
            </a>
        </div>
    </x-slot>

    <div class="page-content">

        {{-- STATISTIQUES DU JOUR --}}
        <div class="stats-bar"
            style="margin-bottom: 1.5rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <div
                style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;">
                <div>
                    <div>CA total du jour</div>
                    <div style="font-size: 1.5rem; font-weight: bold;">
                        {{ number_format($caTotal, 2, ',', ' ') }} €
                    </div>
                </div>

                <div>
                    <div>Vinyles vendus</div>
                    <div>
                        <strong>{{ $nbVinylesTotal }}</strong>
                        @if ($nbVinylesTotal > 0)
                            (dont <strong>{{ $nbMiroirs }}</strong> miroir)
                        @endif
                    </div>
                </div>

                <div>
                    <div>Répartition par mode de paiement</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.25rem;">
                        @forelse($caParMode as $mode => $montant)
                            <span class="badge badge-info">
                                {{ ucfirst($mode) }} :
                                {{ number_format($montant, 2, ',', ' ') }} €
                            </span>
                        @empty
                            <span class="text-muted">Aucun paiement ce jour</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- LISTE DES VENTES DU JOUR --}}
        <div class="table-responsive" style="margin-bottom: 2rem;">
            <h3>Ventes du jour</h3>
            <table class="vinyle-table">
                <thead>
                    <tr>
                        <th>Heure</th>
                        <th>Articles</th>
                        <th>Total</th>
                        <th>Paiement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventes as $vente)
                        <tr>
                            <td>{{ $vente->created_at->format('H:i') }}</td>
                            <td>{{ $vente->lignes->count() }} ligne(s)</td>
                            <td><strong>{{ number_format($vente->total, 2, ',', ' ') }} €</strong></td>
                            <td>
                                <span class="badge badge-info">
                                    {{ ucfirst($vente->mode_paiement) }}
                                </span>
                            </td>
                            <td style="display: flex; gap: 0.5rem;">
                                <a href="{{ route('ventes.show', $vente) }}" class="btn btn-sm btn-secondary">
                                    Détails
                                </a>

                                <form method="POST" action="{{ route('ventes.destroy', $vente) }}"
                                    onsubmit="return confirm('Annuler cette vente ? Les stocks seront restaurés.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Annuler
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty

                        <tr>
                            <td colspan="5" class="text-center">Aucune vente pour ce jour</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- STATS PAR ARTISTE / MODÈLE --}}
        @if ($parArtiste->count())
            <div class="table-responsive" style="margin-bottom: 2rem;">
                <h3>Statistiques par artiste / modèle</h3>
                <table class="vinyle-table">
                    <thead>
                        <tr>
                            <th>Artiste / Modèle</th>
                            <th>Quantité</th>
                            <th>CA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($parArtiste as $libelle => $stats)
                            <tr>
                                <td>{{ $libelle }}</td>
                                <td>{{ $stats['quantite'] }}</td>
                                <td>{{ number_format($stats['ca'], 2, ',', ' ') }} €</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- STATS PAR TYPE DE FOND --}}
        @if ($parFond->count())
            <div class="table-responsive">
                <h3>Statistiques par type de fond</h3>
                <table class="vinyle-table">
                    <thead>
                        <tr>
                            <th>Fond</th>
                            <th>Quantité</th>
                            <th>CA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($parFond as $fond => $stats)
                            <tr>
                                <td>{{ ucfirst($fond) }}</td>
                                <td>{{ $stats['quantite'] }}</td>
                                <td>{{ number_format($stats['ca'], 2, ',', ' ') }} €</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- NAVIGATION JOURS EN BAS --}}
        <div class="pagination-wrapper" style="margin-top: 2rem; display: flex; justify-content: center; gap: 1rem;">
            @if ($previousDate)
                <a href="{{ route('ventes.index', ['date' => $previousDate]) }}" class="btn btn-secondary">
                    ⟵ Jour précédent
                </a>
            @else
                <button class="btn btn-secondary" disabled>⟵ Jour précédent</button>
            @endif

            @if ($nextDate)
                <a href="{{ route('ventes.index', ['date' => $nextDate]) }}" class="btn btn-secondary">
                    Jour suivant ⟶
                </a>
            @else
                <button class="btn btn-secondary" disabled>Jour suivant ⟶</button>
            @endif
        </div>
    </div>
</x-app-layout>
