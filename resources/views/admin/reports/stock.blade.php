@extends('layouts.admin')

@section('title', 'Rapport de Stock')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">Rapport de Stock Global</h1>
    
    {{-- Résumé Global --}}
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm text-gray-600">Valorisation Totale</div>
            <div class="text-2xl font-bold">{{ number_format($totalStockValue, 2, ',', ' ') }} €</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm text-gray-600">Quantité Totale</div>
            <div class="text-2xl font-bold">{{ $totalQuantity }}</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm text-gray-600">Vinyles (Références)</div>
            <div class="text-2xl font-bold">{{ $vinylesCount }}</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm text-gray-600">Fonds</div>
            <div class="text-2xl font-bold">{{ $totalFondsQuantity }}</div>
        </div>
    </div>
    
    {{-- Vinyles vs Fonds --}}
    <div class="grid grid-cols-2 gap-6 mb-6">
        <div class="bg-white p-6 rounded shadow">
            <h2 class="text-lg font-semibold mb-4">Vinyles</h2>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span>Quantité :</span>
                    <span class="font-semibold">{{ $totalVinylesQuantity }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Valeur :</span>
                    <span class="font-semibold">{{ number_format($totalVinylesValue, 2, ',', ' ') }} €</span>
                </div>
                <div class="flex justify-between">
                    <span>Stock bas :</span>
                    <span class="font-semibold {{ $lowStockVinyles->count() > 0 ? 'text-yellow-600' : '' }}">
                        {{ $lowStockVinyles->count() }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Ruptures :</span>
                    <span class="font-semibold {{ $outOfStockVinyles->count() > 0 ? 'text-red-600' : '' }}">
                        {{ $outOfStockVinyles->count() }}
                    </span>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded shadow">
            <h2 class="text-lg font-semibold mb-4">Fonds</h2>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span>Quantité :</span>
                    <span class="font-semibold">{{ $totalFondsQuantity }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Valeur :</span>
                    <span class="font-semibold">{{ number_format($totalFondsValue, 2, ',', ' ') }} €</span>
                </div>
            </div>
            
            <div class="mt-4 space-y-1">
                @foreach($fondsByType as $type => $data)
                    <div class="flex justify-between text-sm">
                        <span>{{ ucfirst($type) }} :</span>
                        <span>{{ $data['quantity'] }} unités</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    
    {{-- Répartition par catégorie --}}
    <div class="bg-white rounded shadow mb-6">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Répartition par Genre</h2>
        </div>
        <div class="p-4">
            <table class="w-full text-left">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2">Genre</th>
                        <th class="px-4 py-2">Références</th>
                        <th class="px-4 py-2">Stock</th>
                        <th class="px-4 py-2">Valeur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vinylesByGenre as $genre => $data)
                    <tr class="border-b">
                        <td class="px-4 py-2">{{ $genre ?: 'Non classé' }}</td>
                        <td class="px-4 py-2">{{ $data['count'] }}</td>
                        <td class="px-4 py-2">{{ $data['quantity'] }}</td>
                        <td class="px-4 py-2">{{ number_format($data['value'], 2, ',', ' ') }} €</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    {{-- Alertes Stock --}}
    @if($lowStockVinyles->count() > 0)
    <div class="bg-yellow-50 border border-yellow-200 rounded shadow mb-6">
        <div class="p-4 border-b border-yellow-200">
            <h2 class="text-lg font-semibold text-yellow-800">⚠️ Alertes Stock Bas</h2>
        </div>
        <div class="p-4">
            <ul class="space-y-1">
                @foreach($lowStockVinyles as $vinyle)
                <li class="text-sm">{{ $vinyle->artiste }} - {{ $vinyle->modele }} ({{ $vinyle->quantite }} restant)</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
</div>
@endsection