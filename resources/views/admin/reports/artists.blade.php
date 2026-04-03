@extends('layouts.admin')

@section('title', 'Rapport par Artiste')

@section('content')
<div class="container mx-auto px-4 py-6">
    
    <h1 class="text-2xl font-bold mb-6">Rapport par Artiste</h1>
    
    {{-- Filtre par lettre --}}
    <div class="bg-white p-4 rounded shadow mb-6">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.reports.artists') }}" 
               class="px-3 py-1 rounded {{ $letter ? 'bg-gray-200' : 'bg-blue-500 text-white' }}">Tous</a>
            @foreach($alphabet as $l)
                <a href="{{ route('admin.reports.artists', ['letter' => $l]) }}" 
                   class="px-3 py-1 rounded {{ $letter === $l ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">{{ $l }}</a>
            @endforeach
        </div>
    </div>
    
    {{-- Tableau des artistes --}}
    <div class="bg-white rounded shadow">
        <div class="p-4 border-b">
            <p class="text-gray-600">{{ $artists->count() }} artiste(s) trouvé(s)</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3">Artiste</th>
                        <th class="px-4 py-3 text-right">Titres</th>
                        <th class="px-4 py-3 text-right">Stock</th>
                        <th class="px-4 py-3 text-right">Valeur Stock</th>
                        <th class="px-4 py-3 text-right">Vendus</th>
                        <th class="px-4 py-3 text-right">CA Vendu</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($artists as $artist)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $artist->artiste }}</td>
                        <td class="px-4 py-3 text-right">{{ $artist->titres_count }}</td>
                        <td class="px-4 py-3 text-right">{{ $artist->stock_quantity }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ number_format($artist->stock_value, 2, ',', ' ') }} €</td>
                        <td class="px-4 py-3 text-right">{{ $artist->quantite_vendue }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($artist->ca_vendu, 2, ',', ' ') }} €</td>
                        <td class="px-4 py-3">
                            @if($artist->has_out_of_stock)
                                <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-800">Rupture</span>
                            @elseif($artist->has_low_stock)
                                <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-800">Stock bas</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">OK</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            Aucun artiste trouvé{{ $letter ? ' pour la lettre ' . $letter : '' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
</div>
@endsection