@extends('layouts.public')

@section('title', $post->title)

@section('content')
<div class="max-w-3xl mx-auto">
    <a href="/" class="text-sm text-slate-500 hover:text-slate-900 mb-4 inline-block">&larr; Retour aux actualites</a>

    <article class="bg-white rounded-lg border border-slate-200 p-6 md:p-8">
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-3">
            <span class="px-2 py-0.5 rounded-full" style="background-color: {{ $post->category->color ?? '#e2e8f0' }}20; color: {{ $post->category->color ?? '#64748b' }}">
                {{ $post->category->name ?? 'Sans categorie' }}
            </span>
            <span>Publié le {{ $post->published_at->format('d/m/Y') }}</span>
        </div>

        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mb-4">{{ $post->title }}</h1>

        <div class="prose prose-slate max-w-none text-slate-700 leading-relaxed">
            {!! nl2br(e($post->content)) !!}
        </div>
    </article>
</div>
@endsection
