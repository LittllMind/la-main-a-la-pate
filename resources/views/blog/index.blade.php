@extends('layouts.public')

@section('title', 'Veille IA Agentique — La Main à la Pâte')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">
    <header class="mb-12 text-center">
        <h1 class="text-4xl font-bold mb-4 text-white">Veille IA Agentique</h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto">
            Retours d'expérience, analyses et veille technique sur l'intelligence artificielle agentique.
            Partagé avec les amis tech du village et au-delà.
        </p>
    </header>

    @if($posts->count() === 0)
        <div class="text-center py-20">
            <p class="text-gray-500 text-lg">Aucun article publié pour le moment.</p>
            <p class="text-gray-600 mt-2">Revenez bientôt !</p>
        </div>
    @else
        <div class="space-y-8">
            @foreach($posts as $post)
            <article class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-purple-500 transition group">
                <div class="flex flex-col md:flex-row md:items-start gap-6">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="text-xs font-medium px-2 py-1 rounded bg-purple-900 text-purple-300">
                                {{ $post->published_at->format('d M Y') }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ $post->published_at->diffForHumans() }}
                            </span>
                        </div>

                        <h2 class="text-2xl font-bold mb-3 group-hover:text-purple-400 transition">
                            <a href="{{ route('blog.show', $post->slug) }}">
                                {{ $post->title }}
                            </a>
                        </h2>

                        @if($post->excerpt)
                        <p class="text-gray-400 leading-relaxed mb-4">
                            {{ $post->excerpt }}
                        </p>
                        @endif

                        <a href="{{ route('blog.show', $post->slug) }}" class="inline-flex items-center text-purple-400 hover:text-purple-300 font-medium transition">
                            Lire l'article
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </article>
            @endforeach
        </div>

        <div class="mt-12">
            {{ $posts->links() }}
        </div>
    @endif
</div>
@endsection
