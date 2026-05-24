@extends('layouts.public')

@section('title', 'Actualites du Rozier')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-slate-900 mb-2">Actualites du Rozier</h1>
    <p class="text-slate-500 mb-8">Les dernieres publications de la commune.</p>

    @forelse($posts as $post)
        <article class="bg-white rounded-lg border border-slate-200 p-6 mb-4 hover:border-slate-300 transition">
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
                <span class="px-2 py-0.5 rounded-full" style="background-color: {{ $post->category->color ?? '#e2e8f0' }}20; color: {{ $post->category->color ?? '#64748b' }}">
                    {{ $post->category->name ?? 'Sans categorie' }}
                </span>
                <span>{{ $post->published_at->format('d/m/Y') }}</span>
            </div>
            <h2 class="text-xl font-semibold text-slate-900 mb-2">
                <a href="{{ route('posts.show', $post->slug) }}" class="hover:underline">
                    {{ $post->title }}
                </a>
            </h2>
            <p class="text-slate-600 text-sm leading-relaxed">{{ $post->excerpt }}</p>
        </article>
    @empty
        <div class="text-center text-slate-500 py-12">
            Aucune publication pour le moment.
        </div>
    @endforelse

    <div class="mt-6">
        {{ $posts->links() }}
    </div>

    <div class="mt-12 p-6 bg-slate-900 rounded-lg text-white text-center">
        <h3 class="text-lg font-semibold mb-2">Espace membres</h3>
        <p class="text-slate-300 text-sm mb-4">Rejoignez les forums de discussion pour echanger avec vos voisins.</p>
        <a href="{{ route('community.index') }}" class="inline-block bg-white text-slate-900 px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-100">
            Acceder a la communaute
        </a>
    </div>
</div>
@endsection
