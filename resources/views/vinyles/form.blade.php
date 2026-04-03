@php
$isEdit = isset($vinyle) && $vinyle->id !== null;
@endphp

@extends('layouts.app')

@section('title', $isEdit ? '✏️ Modifier : ' . $vinyle->nom : '➕ Nouveau Vinyle')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
            {{ $isEdit ? '✏️ Modifier : ' . $vinyle->nom : '➕ Nouveau Vinyle' }}
        </h2>
        <a href="{{ route('vinyles.index') }}" class="btn btn-secondary">
            ← Retour
        </a>
    </div>

    <div class="page-content">
        <form action="{{ $isEdit ? route('vinyles.update', $vinyle) : route('vinyles.store') }}" 
              method="POST" 
              enctype="multipart/form-data"
              class="max-w-4xl">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Colonne gauche : infos de base --}}
                <div class="space-y-4">
                    {{-- Nom --}}
                    <div>
                        <label for="nom" class="block text-sm font-medium text-gray-300 mb-1">Nom du vinyle *</label>
                        <input type="text" name="nom" id="nom" value="{{ old('nom', $vinyle->nom ?? '') }}" required
                               class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-purple-500"
                        >
                        @error('nom')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Artiste --}}
                    <div>
                        <label for="artiste" class="block text-sm font-medium text-gray-300 mb-1">Artiste *</label>
                        <input type="text" name="artiste" id="artiste" value="{{ old('artiste', $vinyle->artiste ?? '') }}" required
                               class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-purple-500"
                        >
                        @error('artiste')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Modèle --}}
                    <div>
                        <label for="modele" class="block text-sm font-medium text-gray-300 mb-1">Modèle *</label>
                        <input type="text" name="modele" id="modele" value="{{ old('modele', $vinyle->modele ?? '') }}" required
                               class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-purple-500"
                               placeholder="Ex: 33 tours, 45 tours..."
                        >
                        @error('modele')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Référence --}}
                    <div>
                        <label for="reference" class="block text-sm font-medium text-gray-300 mb-1">Référence</label>
                        <input type="text" name="reference" id="reference" value="{{ old('reference', $vinyle->reference ?? '') }}"
                               class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-purple-500"
                        >
                        @error('reference')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Colonne droite : prix, stock, photos --}}
                <div class="space-y-4">
                    {{-- Prix et Stock sur la même ligne --}}
                    <div class="grid grid-cols-2 gap-4">
                        {{-- Prix --}}
                        <div>
                            <label for="prix" class="block text-sm font-medium text-gray-300 mb-1">Prix (€) *</label>
                            <input type="number" name="prix" id="prix" value="{{ old('prix', $vinyle->prix ?? '') }}" 
                                   step="0.01" min="0" required
                                   class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-purple-500"
                            >
                            @error('prix')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Quantité --}}
                        <div>
                            <label for="quantite" class="block text-sm font-medium text-gray-300 mb-1">Quantité en stock *</label>
                            <input type="number" name="quantite" id="quantite" value="{{ old('quantite', $vinyle->quantite ?? '') }}" 
                                   min="0" required
                                   class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-purple-500"
                            >
                            @error('quantite')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Genre et Style (optionnels) --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="genre" class="block text-sm font-medium text-gray-300 mb-1">Genre</label>
                            <input type="text" name="genre" id="genre" value="{{ old('genre', $vinyle->genre ?? '') }}"
                                   class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-purple-500"
                            >
                        </div>
                        <div>
                            <label for="style" class="block text-sm font-medium text-gray-300 mb-1">Style</label>
                            <input type="text" name="style" id="style" value="{{ old('style', $vinyle->style ?? '') }}"
                                   class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-purple-500"
                            >
                        </div>
                    </div>

                    {{-- Photos --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Photos (max 3)</label>
                        
                        {{-- Photos existantes --}}
                        @if($isEdit)
                            @php $photos = $vinyle->getMedia('photo'); @endphp
                            @if($photos->count() > 0)
                                <div class="grid grid-cols-3 gap-2 mb-4">
                                    @foreach($photos as $media)
                                        <div class="relative group">
                                            <img src="{{ $media->getUrl('thumb') }}" class="w-full h-24 object-cover rounded">
                                            <label class="absolute top-1 left-1 bg-red-600 text-white text-xs px-2 py-1 rounded cursor-pointer opacity-0 group-hover:opacity-100 transition">
                                                <input type="checkbox" name="delete_photos[]" value="{{ $media->id }}" class="mr-1">
                                                Suppr.
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        {{-- Upload nouvelles photos --}}
                        <div>
                            <input type="file" name="photos[]" id="photos" multiple accept="image/*"
                                   class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-purple-500"
                            >
                            <p class="text-gray-500 text-sm mt-1">Formats acceptés : JPG, PNG, WEBP. Max 5Mo par image.</p>
                            @error('photos')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            @error('photos.*')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Boutons d'action --}}
            <div class="flex justify-end gap-4 mt-8">
                <a href="{{ route('vinyles.index') }}" class="btn btn-secondary">
                    Annuler
                </a>
                <button type="submit" class="btn btn-primary">
                    {{ $isEdit ? '💾 Enregistrer' : '➕ Créer' }}
                </button>
            </div>
        </form>
    </div>
@endsection
