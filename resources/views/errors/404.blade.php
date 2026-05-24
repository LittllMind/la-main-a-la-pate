{{-- resources/views/errors/404.blade.php --}}
{{-- Vue erreur 404 sécurisée contre XSS --}}

@extends('layouts.public')

@section('title', 'Page non trouvee')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-6xl font-bold text-slate-700 mb-4">404</h1>
        <p class="text-xl text-slate-600 mb-6">Page non trouvee</p>
        <a href="{{ route('home') }}" class="bg-slate-900 text-white px-6 py-3 rounded-lg hover:bg-slate-700 transition">
            Retour a l'accueil
        </a>
    </div>
</div>
@endsection
