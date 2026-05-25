@extends('layouts.public')

@section('title', 'La Seraphotheque')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="text-center mb-12">
        <h1 class="text-3xl font-bold text-slate-900 mb-3">La Seraphotheque</h1>
        <p class="text-slate-600 max-w-2xl mx-auto">
            Espace d'exposition des activites, projets et initiatives locales du Rozier.
            Decouvrez ce qui fait la vie de notre commune.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
        <!-- Carte 1 : Les bougies -->
        <a href="https://les-bougies-de-seraphie.fr" target="_blank" class="group bg-white rounded-lg border border-slate-200 overflow-hidden hover:border-slate-400 hover:shadow-md transition">
            <div class="h-40 bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center">
                <span class="text-5xl">🕯️</span>
            </div>
            <div class="p-5">
                <h3 class="font-semibold text-slate-900 mb-1 group-hover:text-slate-700">Les Bougies de Seraphie</h3>
                <p class="text-slate-500 text-sm leading-relaxed">Artisanat local a base de cire vegetale. Bougies parfumees fabriquees au village.</p>
                <span class="inline-block mt-3 text-xs text-amber-600 font-medium">Visiter le site &rarr;</span>
            </div>
        </a>

        <!-- Carte 2 : Fundisc -->
        <a href="https://fundisc.fr" target="_blank" class="group bg-white rounded-lg border border-slate-200 overflow-hidden hover:border-slate-400 hover:shadow-md transition">
            <div class="h-40 bg-gradient-to-br from-violet-100 to-purple-100 flex items-center justify-center">
                <span class="text-5xl">🎵</span>
            </div>
            <div class="p-5">
                <h3 class="font-semibold text-slate-900 mb-1 group-hover:text-slate-700">Fundisc</h3>
                <p class="text-slate-500 text-sm leading-relaxed">Disquaire local et evenements musicaux. Vinyles, concerts et culture au Rozier.</p>
                <span class="inline-block mt-3 text-xs text-violet-600 font-medium">Visiter le site &rarr;</span>
            </div>
        </a>

        <!-- Carte 3 : Marche -->
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="h-40 bg-gradient-to-br from-emerald-100 to-green-100 flex items-center justify-center">
                <span class="text-5xl">🥬</span>
            </div>
            <div class="p-5">
                <h3 class="font-semibold text-slate-900 mb-1">Marche du dimanche</h3>
                <p class="text-slate-500 text-sm leading-relaxed">Tous les dimanches de 8h a 13h sur la place de la mairie. Producteurs locaux et artisans.</p>
                <span class="inline-block mt-3 text-xs text-emerald-600 font-medium">Place de la Mairie, dimanches 8h-13h</span>
            </div>
        </div>

        <!-- Carte 4 : Patrimoine -->
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="h-40 bg-gradient-to-br from-rose-100 to-pink-100 flex items-center justify-center">
                <span class="text-5xl">🏛️</span>
            </div>
            <div class="p-5">
                <h3 class="font-semibold text-slate-900 mb-1">Patrimoine</h3>
                <p class="text-slate-500 text-sm leading-relaxed">Chapelle Saint-Etienne, lavoirs, sentiers historiques. Decouvrez l'histoire du Rozier.</p>
            </div>
        </div>

        <!-- Carte 5 : Nature -->
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="h-40 bg-gradient-to-br from-sky-100 to-blue-100 flex items-center justify-center">
                <span class="text-5xl">🌿</span>
            </div>
            <div class="p-5">
                <h3 class="font-semibold text-slate-900 mb-1">Randonnees & Nature</h3>
                <p class="text-slate-500 text-sm leading-relaxed">Circuits balises, faune locale, espaces proteges. Partez a la decouverte de nos paysages.</p>
            </div>
        </div>

        <!-- Carte 6 : Brocante -->
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="h-40 bg-gradient-to-br from-yellow-100 to-amber-100 flex items-center justify-center">
                <span class="text-5xl">🤝</span>
            </div>
            <div class="p-5">
                <h3 class="font-semibold text-slate-900 mb-1">Brocante & Entraide</h3>
                <p class="text-slate-500 text-sm leading-relaxed">Petites annonces, dons, services entre voisins. Donnez, echangez, partagez.</p>
            </div>
        </div>

    </div>

    <!-- CTA Communaute -->
    <div class="bg-slate-900 rounded-lg p-8 text-center text-white">
        <h3 class="text-xl font-semibold mb-3">Rejoignez la communaute</h3>
        <p class="text-slate-300 text-sm mb-6 max-w-lg mx-auto">
            Echangez avec vos voisins, participez aux forums thematiques et restez informe de la vie du village.
        </p>
        @auth
            <a href="{{ route('community.index') }}" class="inline-block bg-white text-slate-900 px-5 py-2.5 rounded-md text-sm font-medium hover:bg-slate-100 transition">
                Acceder aux forums
            </a>
        @else
            <a href="/register" class="inline-block bg-white text-slate-900 px-5 py-2.5 rounded-md text-sm font-medium hover:bg-slate-100 transition">
                Creer un compte
            </a>
            <p class="text-slate-400 text-xs mt-4">Deja membre ? <a href="/login" class="text-white underline">Se connecter</a></p>
        @endauth
    </div>
</div>
@endsection
