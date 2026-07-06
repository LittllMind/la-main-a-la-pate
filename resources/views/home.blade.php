@extends('layouts.public')

@section('title', 'Hall du Rozier')

@section('content')
@foreach($sections as $section)
    @if(! $section->is_active)
        @continue
    @endif

    @if($section->key === 'actualites')
        <section class="max-w-3xl mx-auto px-4 pt-6 pb-4">
            <div class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-2">{{ $section->title }}</h1>
                @if($section->subtitle)
                    <p class="text-slate-500 text-sm">{{ $section->subtitle }}</p>
                @endif
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

    @elseif($section->key === 'espace-membres')
        <section class="max-w-3xl mx-auto px-4 pb-10">
            <div class="mt-2 p-6 bg-slate-900 rounded-lg text-white text-center">
                <h3 class="text-base sm:text-lg font-semibold mb-2">{{ $section->title }}</h3>
                @if($section->subtitle)
                    <p class="text-slate-300 text-sm mb-4">{{ $section->subtitle }}</p>
                @endif
                <a href="{{ route('community.index') }}" class="inline-block bg-white text-slate-900 px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-100">
                    Accéder à la communauté
                </a>
            </div>
        </section>

    @else
        <section class="max-w-5xl mx-auto px-4 py-8">
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                @if($section->title || $section->subtitle)
                    <div class="px-6 pt-6 pb-2">
                        @if($section->subtitle)
                            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full mb-2 inline-block">{{ $section->subtitle }}</span>
                        @endif
                        <h2 class="text-2xl font-bold text-slate-900">{{ $section->title }}</h2>
                    </div>
                @endif

                @if($section->body)
                    <div class="p-6 md:p-8">
                        {!! $section->body !!}
                    </div>
                @endif
            </div>
        </section>
    @endif
@endforeach
@endsection
