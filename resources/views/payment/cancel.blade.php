@extends('layouts.app')

@section('title', 'Paiement annulé')

@section('content')
<div class="max-w-2xl mx-auto text-center py-12">
    <div class="text-6xl mb-6">😔</div>
    <h1 class="text-4xl font-bold text-gray-300 mb-4">
        Paiement annulé
    </h1>
    <p class="text-gray-400 text-lg mb-8">
        Votre paiement a été annulé. Aucune commande n'a été passée.
    </p>

    <a href="{{ route('kiosque') }}" 
        class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold py-3 px-8 rounded-xl transition">
        Retour au catalogue
    </a>
</div>
@endsection