@extends('layouts.app')

@section('title', 'Nouveau Vinyle')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Nouveau Vinyle</h1>
            <a href="{{ route('vinyles.index') }}" class="btn btn-secondary">
                ← Retour à la liste
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6">
            <form action="{{ route('vinyles.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Référence -->
                    <div>
                        <label for="reference" class="block text-sm font-medium text-gray-700 mb-2">
                            Référence <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="reference" id="reference" value="{{ old('reference') }}" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Artiste -->
                    <div>
                        <label for="artiste" class="block text-sm font-medium text-gray-700 mb-2">
                            Artiste <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="artiste" id="artiste" value="{{ old('artiste') }}" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Modèle -->
                    <div>
                        <label for="modele" class="block text-sm font-medium text-gray-700 mb-2">
                            Modèle
                        </label>
                        <input type="text" name="modele" id="modele" value="{{ old('modele') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Genre -->
                    <div>
                        <label for="genre" class="block text-sm font-medium text-gray-700 mb-2">
                            Genre
                        </label>
                        <input type="text" name="genre" id="genre" value="{{ old('genre') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Style -->
                    <div>
                        <label for="style" class="block text-sm font-medium text-gray-700 mb-2">
                            Style
                        </label>
                        <input type="text" name="style" id="style" value="{{ old('style') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Prix -->
                    <div>
                        <label for="prix" class="block text-sm font-medium text-gray-700 mb-2">
                            Prix (€) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="prix" id="prix" value="{{ old('prix') }}" step="0.01" min="0" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Quantité -->
                    <div>
                        <label for="quantite" class="block text-sm font-medium text-gray-700 mb-2">
                            Quantité <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="quantite" id="quantite" value="{{ old('quantite', 0) }}" min="0" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Seuil Alerte -->
                    <div>
                        <label for="seuil_alerte" class="block text-sm font-medium text-gray-700 mb-2">
                            Seuil d'alerte stock
                        </label>
                        <input type="number" name="seuil_alerte" id="seuil_alerte" value="{{ old('seuil_alerte', 3) }}" min="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Alerte quand stock ≤ cette valeur</p>
                    </div>
                </div>

                <!-- Images -->
                <div class="mt-6">
                    <label for="images" class="block text-sm font-medium text-gray-700 mb-2">
                        Images (max 3)
                    </label>
                    <input type="file" name="images[]" id="images" multiple accept="image/*"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Formats acceptés: JPEG, PNG, JPG, WEBP. Max 2Mo par image.</p>
                </div>

                <!-- Boutons -->
                <div class="mt-8 flex justify-end space-x-4">
                    <a href="{{ route('vinyles.index') }}" class="btn btn-secondary">
                        Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Créer le vinyle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
