@extends('layouts.public')

@section('title', 'Politique de confidentialite')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-8">
        <a href="{{ route('home') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Retour aux actualites</a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-6 md:p-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-4">Politique de confidentialite</h1>

        <h2 class="text-lg font-semibold text-slate-900 mt-6 mb-2">Donnees collectees</h2>
        <p class="text-slate-700 text-sm leading-relaxed mb-3">
            Nous collectons uniquement les donnees necessaires au fonctionnement du site :
        </p>
        <ul class="list-disc list-inside text-slate-700 text-sm leading-relaxed space-y-1 mb-4">
            <li>Nom, pseudonyme, email et commune lors de l'inscription.</li>
            <li>Messages publies sur les forums.</li>
            <li>Donnees de contact via le formulaire.</li>
        </ul>

        <h2 class="text-lg font-semibold text-slate-900 mt-6 mb-2">Utilisation des donnees</h2>
        <p class="text-slate-700 text-sm leading-relaxed">
            Les donnees sont utilisees uniquement pour la gestion du site et l'affichage des contenus.
            Aucune donnee n'est revendue a des tiers. Aucun profilage commercial n'est effectue.
        </p>

        <h2 class="text-lg font-semibold text-slate-900 mt-6 mb-2">Droits des utilisateurs</h2>
        <p class="text-slate-700 text-sm leading-relaxed">
            Conformement au RGPD, vous disposez d'un droit d'acces, de rectification et de suppression de vos donnees.
            Vous pouvez exercer ces droits depuis votre profil ou en contactant l'administrateur du site.
        </p>

        <h2 class="text-lg font-semibold text-slate-900 mt-6 mb-2">Cookies</h2>
        <p class="text-slate-700 text-sm leading-relaxed">
            Ce site utilise des cookies techniques essentiels au fonctionnement (session, authentification).
            Aucun cookie de suivi ou publicitaire n'est depose.
        </p>

        <h2 class="text-lg font-semibold text-slate-900 mt-6 mb-2">Hebergement</h2>
        <p class="text-slate-700 text-sm leading-relaxed">
            Les donnees sont hebergees en Europe (Hostinger) dans le respect des normes de securite.
        </p>
    </div>
</div>
@endsection
