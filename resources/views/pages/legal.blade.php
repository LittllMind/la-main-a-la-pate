@extends('layouts.public')

@section('title', 'Mentions legales')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-8">
        <a href="{{ route('home') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Retour aux actualites</a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-6 md:p-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-4">Mentions legales</h1>

        <h2 class="text-lg font-semibold text-slate-900 mt-6 mb-2">Editeur du site</h2>
        <p class="text-slate-700 text-sm leading-relaxed">
            Site communautaire du Rozier, gere par les habitants du village.
        </p>

        <h2 class="text-lg font-semibold text-slate-900 mt-6 mb-2">Hebergement</h2>
        <p class="text-slate-700 text-sm leading-relaxed">
            Hostinger International Ltd.<br>
            61 Lordou Vironos Street, 6023 Larnaca, Chypre.
        </p>

        <h2 class="text-lg font-semibold text-slate-900 mt-6 mb-2">Propriete intellectuelle</h2>
        <p class="text-slate-700 text-sm leading-relaxed">
            L'ensemble des contenus de ce site est la propriete exclusive du site ou de ses partenaires.
            Toute reproduction ou utilisation sans autorisation prealable est interdite.
        </p>

        <h2 class="text-lg font-semibold text-slate-900 mt-6 mb-2">Responsabilite</h2>
        <p class="text-slate-700 text-sm leading-relaxed">
            Les informations publiees sur ce site le sont a titre indicatif. L'editeur ne peut etre tenu responsable
            de l'exactitude des informations communiquées par les utilisateurs ou des forums.
        </p>
    </div>
</div>
@endsection
