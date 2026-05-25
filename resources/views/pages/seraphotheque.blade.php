@extends('layouts.public')

@section('title', 'La Séraphothèque — Le Rozier')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">

    {{-- HERO --}}
    <div class="text-center mb-14">
        <span class="inline-block text-6xl mb-4">🏠</span>
        <h1 class="text-4xl font-extrabold text-slate-900 mb-3 tracking-tight">La Séraphothèque</h1>
        <p class="text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
            Friperie, brocante et recyclerie au cœur du Rozier.<br>
            Un commerce de proximité, un espace de rencontre, une initiative locale.
        </p>
        <div class="mt-4 text-sm text-slate-500">
            2 rue Louis Armand — 48150 Le Rozier — Ouvert à l'année
        </div>
    </div>

    {{-- 4 PILIERS AVEC PHOTOS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-14">
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden hover:shadow-md transition">
            <div class="h-44 overflow-hidden">
                <img src="/images/seraphotheque/recyclerie.jpg" alt="Recyclerie textile et objets" class="w-full h-full object-cover hover:scale-105 transition">
            </div>
            <div class="p-4 text-center">
                <div class="text-2xl mb-1">♻️</div>
                <h3 class="font-semibold text-slate-900 text-sm mb-1">Recyclerie</h3>
                <p class="text-slate-500 text-xs leading-relaxed">Textiles et objets récupérés, triés et valorisés localement.</p>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden hover:shadow-md transition">
            <div class="h-44 overflow-hidden">
                <img src="/images/seraphotheque/friperie-01.jpg" alt="Friperie et brocante" class="w-full h-full object-cover hover:scale-105 transition">
            </div>
            <div class="p-4 text-center">
                <div class="text-2xl mb-1">👕</div>
                <h3 class="font-semibold text-slate-900 text-sm mb-1">Friperie & Brocante</h3>
                <p class="text-slate-500 text-xs leading-relaxed">Vêtements et objets accessibles à tous les budgets.</p>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden hover:shadow-md transition">
            <div class="h-44 overflow-hidden">
                <img src="/images/seraphotheque/jouets.jpg" alt="Espace enfants avec jouets" class="w-full h-full object-cover hover:scale-105 transition">
            </div>
            <div class="p-4 text-center">
                <div class="text-2xl mb-1">🧸</div>
                <h3 class="font-semibold text-slate-900 text-sm mb-1">Espace Enfants</h3>
                <p class="text-slate-500 text-xs leading-relaxed">Coin jeu et lecture apprécié des familles du village.</p>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden hover:shadow-md transition">
            <div class="h-44 overflow-hidden">
                <img src="/images/seraphotheque/devanture.jpg" alt="Devanture de la boutique" class="w-full h-full object-cover hover:scale-105 transition">
            </div>
            <div class="p-4 text-center">
                <div class="text-2xl mb-1">🤝</div>
                <h3 class="font-semibold text-slate-900 text-sm mb-1">Commerce de proximité</h3>
                <p class="text-slate-500 text-xs leading-relaxed">Ouvert toute l'année pour les habitants et les visiteurs.</p>
            </div>
        </div>
    </div>

    {{-- HISTOIRE --}}
    <div class="bg-white rounded-lg border border-slate-200 p-6 mb-14">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Depuis 2022</h2>
        <p class="text-slate-600 text-sm leading-relaxed mb-4">
            La Séraphothèque est implantée dans l'ancien bâtiment scolaire du Rozier.
            Exploitée par <strong>Anna El Agri</strong> et <strong>Aurélien Tisserand</strong>,
            parents de deux enfants scolarisés au village, l'activité a démarré sur un bail précaire
            renouvelé tous les six mois. Sans incident pendant quatre ans.
        </p>
        <p class="text-slate-600 text-sm leading-relaxed">
            Au fil du temps, la boutique est devenue bien plus qu'un commerce :
            un <strong>espace de rencontre</strong>, un <strong>lieu de vie</strong> apprécié des enfants et des adultes,
            une <strong>initiative locale, sociale et écologique</strong> participant à la vie du village toute l'année.
        </p>
    </div>

    {{-- REUNIONS PUBLIQUES — accordéon extensible --}}
    <div class="mb-14">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Réunions publiques</h2>

        {{-- Réunion 1 : 24 mai 2026 --}}
        <details class="group bg-white rounded-lg border border-slate-200 overflow-hidden mb-3 open:shadow-sm transition">
            <summary class="flex items-center gap-4 p-4 cursor-pointer list-none select-none hover:bg-slate-50 transition">
                <img src="/images/seraphotheque/reunion-01.jpg" alt="" class="w-20 h-20 rounded-md object-cover flex-shrink-0 border border-slate-100">
                <div class="flex-1 min-w-0">
                    <div class="text-xs text-slate-500 font-medium mb-0.5">24 mai 2026</div>
                    <h3 class="font-semibold text-slate-900 text-sm leading-snug">Première réunion publique — « Commerce en danger »</h3>
                    <p class="text-slate-500 text-xs mt-1 truncate">Une vingtaine de personnes rassemblées devant la boutique.</p>
                </div>
                <svg class="w-5 h-5 text-slate-400 flex-shrink-0 transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
            </summary>
            <div class="px-4 pb-5 pt-1 border-t border-slate-100">
                <div class="text-slate-600 text-sm leading-relaxed mb-4 space-y-3">
                    <p>
                        Réunion publique conviviale organisée en plein air devant la Séraphothèque.
                        La devanture affichait une banderole « Commerce en danger ».
                        Une vingtaine de personnes de tous âges — habitants, soutiens, élus — se sont rassemblées
                        pour échanger sur l'avenir du commerce au cœur du village.
                    </p>
                    <p>
                        Les échanges ont porté sur le cadre juridique précaire du bail,
                        l'absence de projet formalisé de la commune et la nécessité d'un
                        <strong>traitement équitable entre tous les commerces du village</strong>.
                        Aucune décision administrative n'a été annoncée ce jour.
                    </p>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="rounded-lg overflow-hidden border border-slate-200">
                        <img src="/images/seraphotheque/reunion-01.jpg" alt="Devanture avec banderole Commerce en danger" class="w-full h-32 object-cover hover:scale-105 transition">
                    </div>
                    <div class="rounded-lg overflow-hidden border border-slate-200">
                        <img src="/images/seraphotheque/reunion-02.jpg" alt="Réunion en cercle dans la cour" class="w-full h-32 object-cover hover:scale-105 transition">
                    </div>
                    <div class="rounded-lg overflow-hidden border border-slate-200">
                        <img src="/images/seraphotheque/reunion-03.jpg" alt="Participants écoutant attentivement" class="w-full h-32 object-cover hover:scale-105 transition">
                    </div>
                    <div class="rounded-lg overflow-hidden border border-slate-200">
                        <img src="/images/seraphotheque/reunion-04.jpg" alt="Ambiance conviviale au soleil" class="w-full h-32 object-cover hover:scale-105 transition">
                    </div>
                </div>
            </div>
        </details>

        {{-- Réunion 2 : template prêt --}}
        {{--
        <details class="group bg-white rounded-lg border border-slate-200 overflow-hidden mb-3 open:shadow-sm transition">
            <summary class="flex items-center gap-4 p-4 cursor-pointer list-none select-none hover:bg-slate-50 transition">
                <div class="w-20 h-20 rounded-md bg-slate-100 flex items-center justify-center flex-shrink-0 border border-slate-100 text-2xl">📅</div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs text-slate-500 font-medium mb-0.5">JJ mois 2026</div>
                    <h3 class="font-semibold text-slate-900 text-sm leading-snug">Titre de la réunion</h3>
                    <p class="text-slate-500 text-xs mt-1 truncate">Résumé en une ligne...</p>
                </div>
                <svg class="w-5 h-5 text-slate-400 flex-shrink-0 transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
            </summary>
            <div class="px-4 pb-5 pt-1 border-t border-slate-100">
                <p class="text-slate-600 text-sm leading-relaxed mb-4">Compte-rendu ici...</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="rounded-lg overflow-hidden border border-slate-200"><img src="/images/seraphotheque/reunion-future-01.jpg" alt="" class="w-full h-32 object-cover"></div>
                </div>
            </div>
        </details>
        --}}

    </div>


    {{-- CONTEXTE / MOBILISATION (factuel, posé) --}}
    <div class="bg-amber-50 rounded-lg border border-amber-200 p-6 mb-14">
        <h2 class="text-lg font-bold text-amber-900 mb-3">Un projet qui cherche sa stabilité</h2>
        <p class="text-amber-800 text-sm leading-relaxed mb-4">
            Depuis le printemps 2026, l'avenir du local est incertain.
            La commune souhaite reprendre le bâtiment sans projet formalisé à ce jour.
            Les exploitants ont retiré leurs installations extérieures, sollicité une
            <strong>autorisation d'occupation temporaire</strong> et demandé la communication
            de documents administratifs — toujours sans réponse.
        </p>
        <p class="text-amber-800 text-sm leading-relaxed mb-4">
            La démarche des exploitants est simple : obtenir un <strong>cadre stable, transparent
            et appliqué de manière égale à tous les commerces du village</strong>.
            Ils restent disponibles pour tout échange constructif.
        </p>
        <div class="flex flex-wrap gap-3 mt-4">
            <a href="https://www.change.org/p/pour-le-maintien-de-la-séraphothèque-au-cœur-du-rozier-48150"
               target="_blank"
               class="inline-block bg-amber-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-amber-800 transition">
                Signer la pétition
            </a>
            <a href="/contact"
               class="inline-block bg-white text-amber-800 border border-amber-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-amber-100 transition">
                Nous contacter
            </a>
        </div>
    </div>

    {{-- RÉSEAUX --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-14">
        <a href="https://www.instagram.com/seraphotheque/" target="_blank"
           class="group flex items-center gap-3 bg-white rounded-lg border border-slate-200 p-4 hover:border-pink-300 hover:shadow-sm transition">
            <span class="text-2xl">📸</span>
            <div>
                <div class="font-semibold text-slate-900 text-sm">Instagram</div>
                <div class="text-slate-500 text-xs">@seraphotheque</div>
            </div>
        </a>
        <a href="https://www.facebook.com/seraphotheque/" target="_blank"
           class="group flex items-center gap-3 bg-white rounded-lg border border-slate-200 p-4 hover:border-blue-300 hover:shadow-sm transition">
            <span class="text-2xl">👍</span>
            <div>
                <div class="font-semibold text-slate-900 text-sm">Facebook</div>
                <div class="text-slate-500 text-xs">La Séraphothèque | Le Rozier</div>
            </div>
        </a>
    </div>

    {{-- CTA Communauté — masqué pour le moment --}}
    @if(false)
    <div class="bg-slate-900 rounded-lg p-8 text-center text-white">
        <h3 class="text-xl font-semibold mb-3">Le Rozier, c'est aussi vous</h3>
        <p class="text-slate-300 text-sm mb-6 max-w-lg mx-auto">
            Rejoignez les forums de la communauté pour échanger sur la vie du village,
            le patrimoine, la nature et les initiatives locales.
        </p>
        @auth
            <a href="{{ route('community.index') }}"
               class="inline-block bg-white text-slate-900 px-5 py-2.5 rounded-md text-sm font-medium hover:bg-slate-100 transition">
                Accéder aux forums
            </a>
        @else
            <a href="/register"
               class="inline-block bg-white text-slate-900 px-5 py-2.5 rounded-md text-sm font-medium hover:bg-slate-100 transition">
                Créer un compte
            </a>
            <p class="text-slate-400 text-xs mt-4">Déjà membre ? <a href="/login" class="text-white underline">Se connecter</a></p>
        @endauth
    </div>
    @endif

    {{-- CAROUSEL ACTIVITÉS — en bas de page --}}
    <div class="mb-14 -mx-4 sm:mx-0">
        <div class="relative overflow-hidden rounded-lg sm:rounded-xl" id="carousel">
            <div class="flex transition-transform duration-500 ease-out" id="carousel-track">
                {{-- Slide 1 : Recyclerie --}}
                <div class="w-full flex-shrink-0 relative h-72 sm:h-80 lg:h-96">
                    <img src="/images/seraphotheque/recyclerie.jpg" alt="Recyclerie" class="w-full h-full object-cover">
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-6 pt-16">
                        <h3 class="text-white text-xl sm:text-2xl font-bold">Recyclerie</h3>
                        <p class="text-white/80 text-sm sm:text-base mt-1">Textiles et objets récupérés, triés et valorisés localement.</p>
                    </div>
                </div>
                {{-- Slide 2 : Friperie --}}
                <div class="w-full flex-shrink-0 relative h-72 sm:h-80 lg:h-96">
                    <img src="/images/seraphotheque/friperie-01.jpg" alt="Friperie" class="w-full h-full object-cover">
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-6 pt-16">
                        <h3 class="text-white text-xl sm:text-2xl font-bold">Friperie & Brocante</h3>
                        <p class="text-white/80 text-sm sm:text-base mt-1">Vêtements et objets accessibles à tous les budgets.</p>
                    </div>
                </div>
                {{-- Slide 3 : Espace Enfants --}}
                <div class="w-full flex-shrink-0 relative h-72 sm:h-80 lg:h-96">
                    <img src="/images/seraphotheque/jouets.jpg" alt="Espace enfants" class="w-full h-full object-cover">
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-6 pt-16">
                        <h3 class="text-white text-xl sm:text-2xl font-bold">Espace Enfants</h3>
                        <p class="text-white/80 text-sm sm:text-base mt-1">Coin jeu et lecture apprécié des familles du village.</p>
                    </div>
                </div>
                {{-- Slide 4 : Commerce de proximité --}}
                <div class="w-full flex-shrink-0 relative h-72 sm:h-80 lg:h-96">
                    <img src="/images/seraphotheque/devanture.jpg" alt="Devanture boutique" class="w-full h-full object-cover">
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-6 pt-16">
                        <h3 class="text-white text-xl sm:text-2xl font-bold">Commerce de proximité</h3>
                        <p class="text-white/80 text-sm sm:text-base mt-1">Ouvert toute l'année pour les habitants et les visiteurs.</p>
                    </div>
                </div>
                {{-- Slide 5 : Réunion publique --}}
                <div class="w-full flex-shrink-0 relative h-72 sm:h-80 lg:h-96">
                    <img src="/images/seraphotheque/reunion-02.jpg" alt="Réunion publique" class="w-full h-full object-cover">
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-6 pt-16">
                        <h3 class="text-white text-xl sm:text-2xl font-bold">Réunion publique</h3>
                        <p class="text-white/80 text-sm sm:text-base mt-1">24 mai 2026 — Une vingtaine de personnes rassemblées au cœur du village.</p>
                    </div>
                </div>
            </div>

            {{-- Flèches --}}
            <button onclick="carouselPrev()" class="absolute left-3 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-slate-900 w-10 h-10 rounded-full flex items-center justify-center shadow-lg transition z-10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </button>
            <button onclick="carouselNext()" class="absolute right-3 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-slate-900 w-10 h-10 rounded-full flex items-center justify-center shadow-lg transition z-10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </button>

            {{-- Dots --}}
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2 z-10">
                <button onclick="carouselGo(0)" class="carousel-dot w-2 h-2 rounded-full bg-white/60 hover:bg-white transition" data-index="0"></button>
                <button onclick="carouselGo(1)" class="carousel-dot w-2 h-2 rounded-full bg-white/60 hover:bg-white transition" data-index="1"></button>
                <button onclick="carouselGo(2)" class="carousel-dot w-2 h-2 rounded-full bg-white/60 hover:bg-white transition" data-index="2"></button>
                <button onclick="carouselGo(3)" class="carousel-dot w-2 h-2 rounded-full bg-white/60 hover:bg-white transition" data-index="3"></button>
                <button onclick="carouselGo(4)" class="carousel-dot w-2 h-2 rounded-full bg-white/60 hover:bg-white transition" data-index="4"></button>
            </div>
        </div>
    </div>

    @push('head')
    <script>
        let carouselIndex = 0;
        const carouselTotal = 5;
        const track = document.getElementById('carousel-track');

        function carouselUpdate() {
            track.style.transform = 'translateX(-' + (carouselIndex * 100) + '%)';
            document.querySelectorAll('.carousel-dot').forEach((dot, i) => {
                dot.classList.toggle('bg-white', i === carouselIndex);
                dot.classList.toggle('bg-white/60', i !== carouselIndex);
            });
        }

        function carouselNext() {
            carouselIndex = (carouselIndex + 1) % carouselTotal;
            carouselUpdate();
        }

        function carouselPrev() {
            carouselIndex = (carouselIndex - 1 + carouselTotal) % carouselTotal;
            carouselUpdate();
        }

        function carouselGo(i) {
            carouselIndex = i;
            carouselUpdate();
        }

        carouselUpdate();

        // Auto-play toutes les 5 secondes
        let autoplay = setInterval(carouselNext, 5000);

        // Pause au survol
        document.getElementById('carousel').addEventListener('mouseenter', () => clearInterval(autoplay));
        document.getElementById('carousel').addEventListener('mouseleave', () => {
            clearInterval(autoplay);
            autoplay = setInterval(carouselNext, 5000);
        });
    </script>
    @endpush

</div>
@endsection
