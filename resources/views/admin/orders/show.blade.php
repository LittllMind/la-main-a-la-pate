<x-app-layout>
    <x-slot name="header">
        <div class="header-actions" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0;">Commande {{ $order->numero_commande }}</h2>
                <div style="font-size: 0.9rem; color: #6b7280;">
                    Créée le {{ $order->created_at->format('d/m/Y à H:i') }}
                </div>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">
                ← Retour à la liste
            </a>
        </div>
    </x-slot>

    <div class="page-content">
        {{-- ALERTES --}}
        @if(session('success'))
            <div class="alert alert-success" style="margin-bottom: 1rem; padding: 1rem; background: #d1fae5; border-radius: 0.5rem;">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger" style="margin-bottom: 1rem; padding: 1rem; background: #fee2e2; border-radius: 0.5rem;">
                {{ session('error') }}
            </div>
        @endif

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            {{-- COLONNE GAUCHE --}}
            <div>
                {{-- STATUT --}}
                <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                    <h3 style="margin-top: 0;">Statut</h3>
                    <div style="margin-bottom: 1rem;">
                        {!! $order->statutBadge() !!}
                    </div>

                    <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <div style="display: flex; gap: 0.5rem;">
                            <select name="statut" class="form-control">
                                <option value="en_attente" {{ $order->statut == 'en_attente' ? 'selected' : '' }}>🟡 En attente</option>
                                <option value="en_preparation" {{ $order->statut == 'en_preparation' ? 'selected' : '' }}>🔵 En préparation</option>
                                <option value="prete" {{ $order->statut == 'prete' ? 'selected' : '' }}>🟢 Prête</option>
                                <option value="livree" {{ $order->statut == 'livree' ? 'selected' : '' }}>⚪ Livrée</option>
                                <option value="completed" {{ $order->statut == 'completed' ? 'selected' : '' }}>✅ Complétée</option>
                                <option value="annulee" {{ $order->statut == 'annulee' ? 'selected' : '' }}>🔴 Annulée</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Changer</button>
                        </div>
                    </form>

                    @if($order->statut !== 'annulee')
                        <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" style="margin-top: 0.5rem;">
                            @csrf
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Annuler cette commande ?')">
                                Annuler la commande
                            </button>
                        </form>
                    @endif
                </div>

                {{-- INFOS CLIENT --}}
                <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                    <h3 style="margin-top: 0;">Client</h3>
                    @if($order->user)
                        <p><strong>{{ $order->user->name }}</strong></p>
                        <p>{{ $order->user->email }}</p>
                    @else
                        <p><strong>{{ $order->shipping_nom ?? 'Anonyme' }}</strong></p>
                        <p>{{ $order->shipping_email ?? '-' }}</p>
                    @endif
                </div>

                {{-- LIVRAISON --}}
                <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                    <h3 style="margin-top: 0;">Livraison</h3>
                    <p>
                        <strong>{{ $order->shipping_prenom }} {{ $order->shipping_nom }}</strong><br>
                        {{ $order->shipping_adresse }}<br>
                        {{ $order->shipping_code_postal }} {{ $order->shipping_ville }}<br>
                        {{ $order->shipping_pays }}
                    </p>
                    @if($order->shipping_telephone)
                        <p>📞 {{ $order->shipping_telephone }}</p>
                    @endif
                    @if($order->shipping_instructions)
                        <p style="background: #fef3c7; padding: 0.5rem; border-radius: 0.25rem;">
                            <strong>Instructions:</strong> {{ $order->shipping_instructions }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- COLONNE DROITE --}}
            <div>
                {{-- ARTICLES --}}
                <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                    <h3 style="margin-top: 0;">Articles</h3>
                    <table class="vinyle-table" style="margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th>Article</th>
                                <th>Qté</th>
                                <th>Prix</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        @if($item->vinyle)
                                            <strong>{{ $item->vinyle->nom }}</strong><br>
                                            <small>{{ $item->vinyle->modele }}</small>
                                        @else
                                            {{ $item->titre_vinyle }}
                                        @endif
                                    </td>
                                    <td>{{ $item->quantite }}</td>
                                    <td>{{ number_format($item->prix_unitaire, 2, ',', ' ') }} €</td>
                                    <td><strong>{{ number_format($item->total, 2, ',', ' ') }} €</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background: #f3f4f6;">
                                <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                                <td><strong>{{ number_format($order->total, 2, ',', ' ') }} €</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- SOURCE --}}
                <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                    <h3 style="margin-top: 0;">Source</h3>
                    @switch($order->source)
                        @case('web')
                            <span class="badge badge-info">🌐 Web (Stripe)</span>
                            @break
                        @case('kiosque')
                            <span class="badge badge-success">🏪 Kiosque</span>
                            @if($order->vente)
                                <p style="margin-top: 0.5rem;">
                                    <strong>Vente liée:</strong> #{{ $order->vente->id }}<br>
                                    <strong>Date:</strong> {{ $order->vente->date->format('d/m/Y') }}<br>
                                    <strong>Paiement:</strong> {{ ucfirst($order->vente->mode_paiement) }}
                                </p>
                            @endif
                            @break
                        @case('marche')
                            <span class="badge badge-warning">🎪 Marché</span>
                            @if($order->mode_paiement_marche)
                                <p><strong>Paiement:</strong> {{ ucfirst($order->mode_paiement_marche) }}</p>
                            @endif
                            @break
                        @default
                            <span class="badge badge-secondary">{{ $order->source ?? 'Non défini' }}</span>
                    @endswitch
                </div>

                {{-- HISTORIQUE --}}
                <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                    <h3 style="margin-top: 0;">Historique</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li>📅 Créée: {{ $order->created_at->format('d/m/Y H:i') }}</li>
                        @if($order->validee_at)
                            <li>✅ Validée: {{ $order->validee_at->format('d/m/Y H:i') }}</li>
                        @endif
                        @if($order->preparee_at)
                            <li>🔵 Préparation: {{ $order->preparee_at->format('d/m/Y H:i') }}</li>
                        @endif
                        @if($order->prete_at)
                            <li>🟢 Prête: {{ $order->prete_at->format('d/m/Y H:i') }}</li>
                        @endif
                        @if($order->livree_at)
                            <li>⚪ Livrée: {{ $order->livree_at->format('d/m/Y H:i') }}</li>
                        @endif
                        @if($order->annulee_at)
                            <li>🔴 Annulée: {{ $order->annulee_at->format('d/m/Y H:i') }}</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
