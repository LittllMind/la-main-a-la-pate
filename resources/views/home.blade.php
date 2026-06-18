@extends('layouts.public')

@section('title', 'Hall du Rozier')

@section('content')

{{-- ========== ACTUALITÉS DE LA COMMUNE ========== --}}
<section class="max-w-3xl mx-auto px-4 pt-6 pb-4">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Hall du Rozier</h1>
        <p class="text-slate-500 text-sm">Les dernières publications de la commune.</p>
    </div>

    @forelse($posts as $post)
        <article class="bg-white rounded-lg border border-slate-200 p-5 mb-4 hover:border-slate-300 transition">
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
                <span class="px-2 py-0.5 rounded-full" style="background-color: {{ $post->category->color ?? '#e2e8f0' }}20; color: {{ $post->category->color ?? '#64748b' }}">
                    {{ $post->category->name ?? 'Sans catégorie' }}
                </span>
                <span>{{ $post->published_at->format('d/m/Y') }}</span>
            </div>
            <h2 class="text-lg font-semibold text-slate-900 mb-2">
                <a href="{{ route('posts.show', $post->slug) }}" class="hover:underline">
                    {{ $post->title }}
                </a>
            </h2>
            <p class="text-slate-600 text-sm leading-relaxed">{{ $post->excerpt }}</p>
        </article>
    @empty
        <div class="text-center text-slate-500 py-12 bg-white rounded-lg border border-slate-200">
        <div class="text-3xl mb-2">📰</div>
        Aucune publication pour le moment.
        </div>
    @endforelse

    <div class="mt-4">
        {{ $posts->links() }}
    </div>
</section>

{{-- ========== ENCART SÉRAPHOTHÈQUE EN VEDETTE ========== --}}
<section class="max-w-5xl mx-auto px-4 py-8">
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2">
            <div class="h-56 md:h-auto overflow-hidden">
                <img src="/images/seraphotheque/devanture.jpg" alt="Devanture de la Séraphothèque" class="w-full h-full object-cover hover:scale-105 transition duration-500">
            </div>
            <div class="p-6 md:p-8 flex flex-col justify-center">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-2xl">🏠</span>
                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">Commerce de proximité</span>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mb-2">La Séraphothèque</h2>
                <p class="text-slate-600 text-sm leading-relaxed mb-4">
                    Friperie, brocante et recyclerie au cœur du Rozier depuis 2022.
                    Un espace de rencontre, une initiative locale, sociale et écologique.
                </p>
                <div class="text-xs text-slate-500 mb-5">
                    2 rue Louis Armand — 48150 Le Rozier — Ouvert à l'année
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('seraphotheque') }}" class="inline-block bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">
                        Découvrir la boutique
                    </a>
                    <a href="https://www.change.org/p/pour-le-maintien-de-la-seraphotheque-au-cœur-du-rozier-48150" target="_blank" class="inline-block bg-white text-emerald-700 border border-emerald-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-50 transition">
                        Signer la pétition
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ========== ESPACE MEMBRES ========== --}}
<section class="max-w-3xl mx-auto px-4 pb-10">
    <div class="mt-2 p-6 bg-slate-900 rounded-lg text-white text-center">
        <h3 class="text-lg font-semibold mb-2">Espace membres</h3>
        <p class="text-slate-300 text-sm mb-4">Rejoignez les forums de discussion pour échanger avec vos voisins.</p>
        <a href="{{ route('community.index') }}" class="inline-block bg-white text-slate-900 px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-100">
            Accéder à la communauté
        </a>
    </div>
</section>

@endsection
