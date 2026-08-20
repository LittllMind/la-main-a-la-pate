@extends('layouts.public')

@section('title', 'La Main à la Pâte')

@section('content')
<section class="max-w-2xl mx-auto px-4 py-16 text-center">
    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">La Main à la Pâte</h1>
    <p class="text-slate-600 text-lg leading-relaxed mb-8">
        Espace communautaire du village du Rozier.
    </p>
    <p class="text-slate-500 text-sm">
        <a href="{{ route('legal') }}" class="underline hover:text-slate-700">Mentions légales</a>
        ·
        <a href="{{ route('privacy') }}" class="underline hover:text-slate-700">Confidentialité</a>
    </p>
</section>
@endsection
