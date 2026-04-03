@extends('layouts.app')

@section('title', 'Modifier l\'adresse')

@section('content')
<div class="min-h-screen bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-violet-400 to-pink-400 bg-clip-text text-transparent">
                Modifier l'adresse
            </h1>
            <p class="mt-2 text-gray-400">Modifiez les informations de cette adresse</p>
        </div>

        <div class="bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-700">
            <form action="{{ route('addresses.update', $address->id) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Label -->
                <div>
                    <label for="label" class="block text-sm font-medium text-gray-300 mb-1">
                        Label * <span class="text-gray-500 text-xs">(ex: Maison, Travail, etc.)</span>
                    </label>
                    <input type="text" id="label" name="label" required
                        class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                        placeholder="Maison"
                        value="{{ old('label', $address->label) }}">
                    @error('label')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Informations personnelles -->
                <div class="space-y-4 pt-4 border-t border-gray-700">
                    <h3 class="text-lg font-semibold text-violet-400">Informations personnelles</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="nom" class="block text-sm font-medium text-gray-300 mb-1">Nom *</label>
                            <input type="text" id="nom" name="nom" required
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                                placeholder="Votre nom"
                                value="{{ old('nom', $address->nom) }}">
                            @error('nom')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email *</label>
                            <input type="email" id="email" name="email" required
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                                placeholder="votre@email.com"
                                value="{{ old('email', $address->email) }}">
                            @error('email')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="telephone" class="block text-sm font-medium text-gray-300 mb-1">Téléphone *</label>
                        <input type="tel" id="telephone" name="telephone" required
                            class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                            placeholder="06 12 34 56 78"
                            value="{{ old('telephone', $address->telephone) }}">
                        @error('telephone')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Adresse -->
                <div class="space-y-4 pt-4 border-t border-gray-700">
                    <h3 class="text-lg font-semibold text-violet-400">📍 Adresse</h3>

                    <div>
                        <label for="adresse" class="block text-sm font-medium text-gray-300 mb-1">Adresse *</label>
                        <input type="text" id="adresse" name="adresse" required
                            class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                            placeholder="123 Rue de la Musique"
                            value="{{ old('adresse', $address->adresse) }}">
                        @error('adresse')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="code_postal" class="block text-sm font-medium text-gray-300 mb-1">Code postal *</label>
                            <input type="text" id="code_postal" name="code_postal" required pattern="[0-9]{5}"
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                                placeholder="75001"
                                value="{{ old('code_postal', $address->code_postal) }}">
                            @error('code_postal')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="ville" class="block text-sm font-medium text-gray-300 mb-1">Ville *</label>
                            <input type="text" id="ville" name="ville" required
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                                placeholder="Paris"
                                value="{{ old('ville', $address->ville) }}">
                            @error('ville')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="pays" class="block text-sm font-medium text-gray-300 mb-1">Pays *</label>
                        <select id="pays" name="pays" required
                            class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-xl text-white focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
                            <option value="FR" {{ old('pays', $address->pays) === 'FR' ? 'selected' : '' }}>France</option>
                            <option value="BE" {{ old('pays', $address->pays) === 'BE' ? 'selected' : '' }}>Belgique</option>
                            <option value="CH" {{ old('pays', $address->pays) === 'CH' ? 'selected' : '' }}>Suisse</option>
                            <option value="LU" {{ old('pays', $address->pays) === 'LU' ? 'selected' : '' }}>Luxembourg</option>
                            <option value="DE" {{ old('pays', $address->pays) === 'DE' ? 'selected' : '' }}>Allemagne</option>
                            <option value="OTHER" {{ old('pays', $address->pays) === 'OTHER' ? 'selected' : '' }}>Autre</option>
                        </select>
                        @error('pays')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Instructions -->
                <div class="space-y-4 pt-4 border-t border-gray-700">
                    <h3 class="text-lg font-semibold text-violet-400">Instructions (optionnel)</h3>
                    <textarea id="instructions" name="instructions" rows="3"
                        class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all resize-none"
                        placeholder="Code d'accès, digicode, étage, etc.">{{ old('instructions', $address->instructions) }}</textarea>
                    @error('instructions')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Adresse par défaut -->
                <div class="pt-4 border-t border-gray-700">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" id="is_default" name="is_default" value="1"
                            {{ old('is_default', $address->is_default) ? 'checked' : '' }}
                            class="w-5 h-5 rounded border-gray-600 bg-gray-900 text-violet-500 focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 focus:ring-offset-gray-800 transition">
                        <span class="text-gray-300 font-medium">Définir comme adresse par défaut</span>
                    </label>
                </div>

                <!-- Boutons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6">
                    <a href="{{ route('addresses.index') }}"
                        class="flex-1 px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-xl transition-colors text-center">
                        Annuler
                    </a>
                    <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-violet-600 to-pink-600 hover:from-violet-500 hover:to-pink-500 text-white font-semibold rounded-xl transition-all transform hover:scale-105 shadow-lg">
                        Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
