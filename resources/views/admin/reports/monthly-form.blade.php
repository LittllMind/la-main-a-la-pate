@extends('layouts.app')

@section('title', 'Rapport Mensuel')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold mb-6">Générer un Rapport Mensuel</h1>
            
            <form method="POST" action="{{ route('admin.reports.monthly.generate') }}" class="space-y-6">
                @csrf
                
                <div>
                    <label for="month" class="block text-sm font-medium text-gray-700">
                        Mois concerné
                    </label>
                    <input type="month" name="month" id="month" 
                           value="{{ now()->format('Y-m') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           required>
                    @error('month')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="bg-gray-50 p-4 rounded-md">
                    <h3 class="font-semibold mb-2">Le rapport inclura :</h3>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        <li>Ventes du mois (commandes livrées)</li>
                        <li>Top produits vendus</li>
                        <li>Mouvements de stock</li>
                        <li>Inventaire global</li>
                        <li>Valeur totale du stock</li>
                    </ul>
                </div>

                <div class="flex items-center justify-end space-x-3">
                    <a href="{{ route('admin.dashboard') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Annuler
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Télécharger PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection