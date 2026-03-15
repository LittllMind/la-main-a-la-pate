{{-- resources/views/stock-alerts/history.blade.php --}}
@extends('layouts.app')

@section('title', 'Historique Alertes - Fundisc')

@section('content')
<div class="max-w-7xl mx-auto">
    
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
            📜 Historique des Alertes
        </h1>
        <p class="text-gray-400 mt-2">Alertes résolues</p>
    </div>

    <div class="bg-gray-800/50 border border-gray-700 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white">Alertes résolues</h3>
            <a href="{{ route('stock-alerts.index') }}" class="text-purple-400 hover:text-purple-300 text-sm">
                ← Retour aux alertes actives
            </a>
        </div>

        @if($alerts->isEmpty())
            <div class="p-8 text-center text-gray-500">
                <p class="text-4xl mb-4">📋</p>
                <p>Aucune alerte dans l'historique.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Vinyle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Détails</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Créée le</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Mise à jour</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($alerts as $alert)
                        <tr class="opacity-50 hover:bg-gray-700/30">
                            <td class="px-6 py-4">
                                @if($alert->alertable)
                                    {{ $alert->alertable->nom }}
                                @else
                                    <span class="text-gray-500">Vinyle supprimé</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900/50 text-green-400 border border-green-700">
                                    Résolue
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-300">
                                Qté: {{ $alert->quantite_actuelle }} / Seuil: {{ $alert->seuil_alerte }}
                            </td>
                            <td class="px-6 py-4 text-gray-400 text-sm">{{ $alert->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-green-400 text-sm">{{ $alert->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-700">
                {{ $alerts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
