@extends('layouts.app')

@section('title', 'Mes Adresses')

@section('content')
<div class="min-h-screen bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- En-tête -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-violet-400 to-pink-400 bg-clip-text text-transparent">
                    Mes Adresses
                </h1>
                <p class="mt-2 text-gray-400">Gérez vos adresses de livraison et facturation</p>
            </div>
            <a href="{{ route('addresses.create') }}"
                class="px-6 py-3 bg-gradient-to-r from-violet-600 to-pink-600 hover:from-violet-500 hover:to-pink-500 text-white font-semibold rounded-xl transition-all transform hover:scale-105 shadow-lg">
                + Nouvelle adresse
            </a>
        </div>

        <!-- Messages flash -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-900/50 border border-green-700 rounded-xl text-green-400">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-900/50 border border-red-700 rounded-xl text-red-400">
                {{ session('error') }}
            </div>
        @endif

        <!-- Liste des adresses -->
        @if($addresses->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($addresses as $address)
                    <div class="bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-700 {{ $address->is_default ? 'ring-2 ring-violet-500' : '' }}">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-xl font-bold text-white flex items-center">
                                    {{ $address->label }}
                                    @if($address->is_default)
                                        <span class="ml-2 px-2 py-1 text-xs bg-violet-600 text-white rounded-full">Par défaut</span>
                                    @endif
                                </h3>
                            </div>
                            <div class="flex space-x-2">
                                <a href="{{ route('addresses.edit', $address->id) }}"
                                    class="p-2 text-gray-400 hover:text-violet-400 transition-colors"
                                    title="Modifier">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                @if(!$address->is_default)
                                    <form action="{{ route('addresses.destroy', $address->id) }}" method="POST" 
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette adresse ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-400 hover:text-red-400 transition-colors"
                                            title="Supprimer">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-2 text-gray-300">
                            <p class="font-semibold text-white">{{ $address->nom }}</p>
                            <p>{{ $address->adresse }}</p>
                            <p>{{ $address->code_postal }} {{ $address->ville }}</p>
                            <p>{{ strtoupper($address->pays) }}</p>
                            <p class="text-sm text-gray-400">{{ $address->telephone }}</p>
                            <p class="text-sm text-gray-400">{{ $address->email }}</p>
                            
                            @if($address->instructions)
                                <div class="mt-3 pt-3 border-t border-gray-700">
                                    <p class="text-xs text-gray-500">📝 {{ $address->instructions }}</p>
                                </div>
                            @endif
                        </div>

                        @if(!$address->is_default)
                            <form action="{{ route('addresses.setDefault', $address->id) }}" method="POST" class="mt-4">
                                @csrf
                                <button type="submit"
                                    class="w-full px-4 py-2 bg-gray-700 hover:bg-violet-600 text-white text-sm font-medium rounded-lg transition-colors">
                                    Définir comme adresse par défaut
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-16 bg-gray-800 rounded-2xl border border-gray-700">
                <svg class="mx-auto h-16 w-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <h3 class="mt-4 text-xl font-semibold text-white">Aucune adresse enregistrée</h3>
                <p class="mt-2 text-gray-400">Ajoutez votre première adresse pour commander plus rapidement</p>
                <a href="{{ route('addresses.create') }}"
                    class="mt-6 inline-block px-6 py-3 bg-gradient-to-r from-violet-600 to-pink-600 hover:from-violet-500 hover:to-pink-500 text-white font-semibold rounded-xl transition-all">
                    + Ajouter une adresse
                </a>
            </div>
        @endif

        <!-- Bouton retour -->
        <div class="mt-8">
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center text-gray-400 hover:text-white transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Retour au tableau de bord
            </a>
        </div>
    </div>
</div>
@endsection
