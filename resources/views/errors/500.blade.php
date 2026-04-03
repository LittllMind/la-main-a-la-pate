{{-- resources/views/errors/500.blade.php --}}
{{-- Vue erreur 500 sans exposition de données sensibles --}}

@extends('layouts.app')

@section('title', 'Erreur serveur')

@section('content')
<div class="min-h-screen flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-6xl font-bold text-gray-700 mb-4">500</h1>
        <p class="text-xl text-gray-600 mb-6">Une erreur est survenue</p>
        <p class="text-gray-500 mb-6">Notre équipe a été notifiée.</p>
        <a href="{{ route('kiosque.index') }}" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700">
            Retour au catalogue
        </a>
    </div>
</div>
@endsection
