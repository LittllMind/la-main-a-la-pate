<!-- resources/views/admin/dashboard.blade.php -->
@extends('layouts.app')

@section('title', 'Tableau de bord Admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Tableau de bord</h1>
        <p class="text-gray-600 mt-2">Vue d'ensemble de l'activité du {{ now()->format('d/m/Y') }}</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <!-- Ventes du mois -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 uppercase">Ventes du mois</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($ventesMois, 2, ',', ' ') }} €</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Commandes en cours -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 uppercase">Commandes en cours</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $commandesEnCours }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Valeur Stock Vinyles -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 uppercase">Stock Vinyles</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($valeurStockVinyles, 2, ',', ' ') }} €</p>
                    <p class="text-sm text-gray-500">{{ $totalVinyles }} unités</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Valeur Stock Fonds -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 uppercase">Stock Fonds</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($valeurStockFonds, 2, ',', ' ') }} €</p>
                    <p class="text-sm text-gray-500">{{ $totalFonds }} unités</p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-full">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertes et Dernières Commandes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <!-- Alertes Stock -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Alertes Stock</h2>
                <span class="text-sm text-blue-600 hover:underline cursor-pointer">Voir tout →</span>
            </div>
            
            <div class="space-y-3">
                @if($alertesVinyles > 0 || $rupturesVinyles > 0 || $rupturesFonds > 0)
                    @if($rupturesVinyles > 0)
                        <div class="flex items-center p-3 bg-red-50 border border-red-200 rounded-lg">
                            <div class="p-2 bg-red-100 rounded-full mr-3">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-red-800">{{ $rupturesVinyles }} rupture(s) de stock vinyle</p>
                            </div>
                        </div>
                    @endif
                    @if($alertesVinyles > 0)
                        <div class="flex items-center p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="p-2 bg-yellow-100 rounded-full mr-3">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-yellow-800">{{ $alertesVinyles }} vinyle(s) en stock faible</p>
                            </div>
                        </div>
                    @endif
                    @if($rupturesFonds > 0)
                        <div class="flex items-center p-3 bg-red-50 border border-red-200 rounded-lg">
                            <div class="p-2 bg-red-100 rounded-full mr-3">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-red-800">{{ $rupturesFonds }} rupture(s) de stock fonds</p>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-6 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>Aucune alerte de stock</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Dernières Commandes -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Dernières Commandes</h2>
                <a href="{{ route('admin.users.index') }}" class="text-sm text-blue-600 hover:underline">Voir tout →</a>
            </div>
            
            @if($dernieresCommandes->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="pb-3">N°</th>
                                <th class="pb-3">Client</th>
                                <th class="pb-3">Total</th>
                                <th class="pb-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($dernieresCommandes as $commande)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 text-sm font-medium text-gray-900">{{ $commande->numero_commande }}</td>
                                    <td class="py-3 text-sm text-gray-600">{{ $commande->nom }} {{ $commande->prenom }}</td>
                                    <td class="py-3 text-sm font-medium text-gray-900">{{ number_format($commande->total, 2, ',', ' ') }} €</td>
                                    <td class="py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ match($commande->statut) {
                                                'en_attente' => 'bg-yellow-100 text-yellow-800',
                                                'en_preparation' => 'bg-blue-100 text-blue-800',
                                                'prete' => 'bg-purple-100 text-purple-800',
                                                'livree' => 'bg-green-100 text-green-800',
                                                'annulee' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            } }}">
                                            {{ ucfirst(str_replace('_', ' ', $commande->statut)) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-center py-6 text-gray-500">Aucune commande récente</p>
            @endif
        </div>
    </div>

    <!-- Graphique des Ventes (version simple avec CSS bars) -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-800">Évolution des Ventes (6 mois)</h2>
        </div>
        
        <div class="relative h-64">
            <div class="flex items-end justify-around h-full pb-8">
                @php
                    $maxVentes = $ventesMensuelles->max('montant') ?: 1;
                @endphp
                @foreach($ventesMensuelles as $vente)
                    @php
                        $height = $maxVentes > 0 ? ($vente['montant'] / $maxVentes) * 100 : 0;
                    @endphp
                    <div class="flex flex-col items-center">
                        <span class="text-xs font-medium text-gray-600 mb-1">{{ number_format($vente['montant'], 0, ',', ' ') }} €</span>
                        <div class="w-16 bg-green-500 rounded-t transition-all duration-500" style="height: {{ $height }}%;"></div>
                        <span class="text-xs text-gray-500 mt-2">{{ $vente['mois'] }}</span>
                    </div>
                @endforeach
            </div>
            
            <!-- Grille horizontale -->
            <div class="absolute inset-0 pointer-events-none">
                @for($i = 0; $i <= 4; $i++)
                    <div class="border-t border-gray-200" style="position: absolute; bottom: {{ $i * 25 }}%;"></div>
                @endfor
            </div>
        </div>
    </div>

</div>
@endsection