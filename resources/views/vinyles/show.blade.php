@extends('layouts.app')

@section('title', $vinyle->nom_complet)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Détail du Vinyle</h1>
            <div class="flex space-x-2">
                <a href="{{ route('vinyles.index') }}" class="btn btn-secondary">
                    ← Retour à la liste
                </a>
                <a href="{{ route('vinyles.edit', $vinyle) }}" class="btn btn-primary">
                    Modifier
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Images -->
                    <div>
                        @if($vinyle->getMedia('photo')->count() > 0)
                            <div class="space-y-4">
                                <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                                    <img id="mainImage" src="{{ $vinyle->getFirstMediaUrl('photo', 'medium') }}" 
                                         alt="{{ $vinyle->nom_complet }}" class="w-full h-full object-cover">
                                </div>
                                @if($vinyle->getMedia('photo')->count() > 1)
                                    <div class="grid grid-cols-4 gap-2">
                                        @foreach($vinyle->getMedia('photo') as $media)
                                            <button type="button" onclick="document.getElementById('mainImage').src='{{ $media->getUrl('medium') }}'"
                                                    class="aspect-square rounded overflow-hidden bg-gray-100 hover:opacity-75">
                                                <img src="{{ $media->getUrl('thumb') }}" alt="Image {{ $loop->iteration }}" class="w-full h-full object-cover">
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="aspect-square rounded-lg bg-gray-100 flex items-center justify-center">
                                <span class="text-gray-400">Pas d'image</span>
                            </div>
                        @endif
                    </div>

                    <!-- Informations -->
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">{{ $vinyle->artiste }}</h2>
                            @if($vinyle->modele)
                                <p class="text-xl text-gray-600">{{ $vinyle->modele }}</p>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Référence:</span>
                                <span class="font-medium ml-2">{{ $vinyle->reference }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Genre:</span>
                                <span class="font-medium ml-2">{{ $vinyle->genre ?: '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Style:</span>
                                <span class="font-medium ml-2">{{ $vinyle->style ?: '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Prix:</span>
                                <span class="font-medium ml-2 text-lg text-green-600">{{ number_format($vinyle->prix, 2) }} €</span>
                            </div>
                        </div>

                        <div class="border-t pt-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Stock disponible:</span>
                                <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $vinyle->stock_status_class }}">
                                    {{ $vinyle->quantite }} unités
                                </span>
                            </div>
                            @if($vinyle->seuil_alerte)
                                <p class="text-xs text-gray-500 mt-1">Seuil d'alerte: {{ $vinyle->seuil_alerte }}</p>
                            @endif
                        </div>

                        <div class="border-t pt-4">
                            <h3 class="font-semibold text-gray-700 mb-2">Historique</h3>
                            <div class="text-sm space-y-1">
                                <p><span class="text-gray-500">Ventes:</span> {{ $vinyle->ventes->count() }} transactions</p>
                                <p><span class="text-gray-500">Commandes:</span> {{ $vinyle->orderItems->count() }} articles commandés</p>
                            </div>
                        </div>

                        <div class="border-t pt-4 flex space-x-4">
                            <a href="{{ route('vinyles.edit', $vinyle) }}" class="btn btn-primary flex-1 text-center">
                                Modifier
                            </a>
                            <form action="{{ route('vinyles.destroy', $vinyle) }}" method="POST" class="flex-1" 
                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce vinyle ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-full">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
