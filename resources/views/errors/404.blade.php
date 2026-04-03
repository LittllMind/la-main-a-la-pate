{{-- resources/views/errors/404.blade.php --}}
{{-- Vue erreur 404 sécurisée contre XSS --}}

@extends('layouts.app')

@section('title', 'Page non trouvée')

@section('content')
<div class="min-h-screen flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-6xl font-bold text-gray-700 mb-4">404</h1>
        <p class="text-xl text-gray-600 mb-6">Page non trouvée</p>
        <a href="{{ route('kiosque.index') }}" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700">
            Retour au catalogue
        </a>
    </div>
</div>
@endsection
