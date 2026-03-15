<x-app-layout>
    <x-slot name="header">
        <div class="header-actions" style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;">Gestion des Commandes</h2>
        </div>
    </x-slot>

    <div class="page-content">
        {{-- FILTRES --}}
        <form method="GET" action="{{ route('admin.orders.index') }}" style="margin-bottom: 1.5rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
                <div>
                    <label>Statut</label>
                    <select name="statut" class="form-control">
                        <option value="">Tous</option>
                        <option value="en_attente" {{ request('statut') == 'en_attente' ? 'selected' : '' }}>🟡 En attente</option>
                        <option value="en_preparation" {{ request('statut') == 'en_preparation' ? 'selected' : '' }}>🔵 En préparation</option>
                        <option value="prete" {{ request('statut') == 'prete' ? 'selected' : '' }}>🟢 Prête</option>
                        <option value="livree" {{ request('statut') == 'livree' ? 'selected' : '' }}>⚪ Livrée</option>
                        <option value="completed" {{ request('statut') == 'completed' ? 'selected' : '' }}>✅ Complétée</option>
                        <option value="annulee" {{ request('statut') == 'annulee' ? 'selected' : '' }}>🔴 Annulée</option>
                    </select>
                </div>

                <div>
                    <label>Source</label>
                    <select name="source" class="form-control">
                        <option value="">Toutes</option>
                        <option value="web" {{ request('source') == 'web' ? 'selected' : '' }}>🌐 Web (Stripe)</option>
                        <option value="kiosque" {{ request('source') == 'kiosque' ? 'selected' : '' }}>🏪 Kiosque</option>
                        <option value="marche" {{ request('source') == 'marche' ? 'selected' : '' }}>🎪 Marché</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Réinitialiser</a>
                </div>
            </div>
        </form>

        {{-- TABLEAU DES COMMANDES --}}
        <div class="table-responsive">
            <table class="vinyle-table">
                <thead>
                    <tr>
                        <th>N° Commande</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Source</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>
                                <strong>{{ $order->numero_commande }}</strong>
                                @if($order->vente)
                                    <br><small class="text-muted">Vente #{{ $order->vente->id }}</small>
                                @endif
                            </td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($order->user)
                                    {{ $order->user->name }}
                                @else
                                    {{ $order->shipping_nom ?? 'Anonyme' }}
                                @endif
                            </td>
                            <td>
                                @switch($order->source)
                                    @case('web')
                                        <span class="badge badge-info">🌐 Web</span>
                                        @break
                                    @case('kiosque')
                                        <span class="badge badge-success">🏪 Kiosque</span>
                                        @break
                                    @case('marche')
                                        <span class="badge badge-warning">🎪 Marché</span>
                                        @break
                                    @default
                                        <span class="badge badge-secondary">{{ $order->source }}</span>
                                @endswitch
                            </td>
                            <td><strong>{{ number_format($order->total, 2, ',', ' ') }} €</strong></td>
                            <td>{!! $order->statutBadge() !!}</td>
                            <td style="display: flex; gap: 0.5rem;">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-secondary">
                                    Voir
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Aucune commande trouvée</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="pagination-wrapper" style="margin-top: 1.5rem;">
            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>
