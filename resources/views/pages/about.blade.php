@extends('layouts.public')

@section('title', 'A propos')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-8">
        <a href="{{ route('home') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Retour aux actualites</a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-6 md:p-8 mb-6">
        <h1 class="text-2xl font-bold text-slate-900 mb-4">A propos</h1>
        <p class="text-slate-700 leading-relaxed mb-4">
            La Main a la Pate est le site communautaire du <strong>Rozier</strong> et des communes environnantes.
            Il a ete cree pour permettre aux habitants de s'informer, s'exprimer et echanger dans un cadre respectueux et constructif.
        </p>
        <p class="text-slate-700 leading-relaxed mb-4">
            Ce portail propose deux espaces complementaires :
        </p>
        <ul class="list-disc list-inside text-slate-700 leading-relaxed space-y-2 mb-4">
            <li>Les <strong>Actualites</strong> : informations officielles et communiques de la commune.</li>
            <li>La <strong>Communaute</strong> : forums de discussion thematiques ouverts a tous les habitants.</li>
        </ul>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-6 md:p-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Contact</h2>
        <p class="text-slate-700 leading-relaxed mb-4">
            Pour toute question, suggestion ou signalement, utilisez notre <a href="{{ route('contact') }}" class="text-slate-900 font-medium hover:underline">formulaire de contact</a>
            ou ecrivez directement a la mairie du Rozier.
        </p>
    </div>
</div>
@endsection
