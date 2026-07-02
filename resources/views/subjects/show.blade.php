@extends('layouts.public')

@section('title', $subject->title . ' — La Main à la Pâte')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    <div class="mb-6">
        <a href="{{ route('subjects.index') }}" class="text-sm text-slate-500 hover:text-slate-900 mb-2 inline-block">← Retour aux sujets</a>
        <div class="flex items-center gap-2 text-xs mb-2">
            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $subject->theme }}</span>
            @if($subject->status === 'draft')
                <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Brouillon</span>
            @endif
        </div>
        <h1 class="text-3xl font-bold text-slate-900">{{ $subject->title }}</h1>
        <div class="mt-2 flex items-center gap-2 text-sm text-slate-500">
            <span class="inline-block w-3 h-3 rounded-full" style="background-color: {{ $subject->user->color ?: '#64748b' }}"></span>
            Rédigé par {{ $subject->user->name }} — {{ $subject->updated_at->format('d/m/Y') }}
        </div>
    </div>

    <article class="bg-white rounded-lg border border-slate-200 p-6 mb-8 subject-document">
        <div class="prose prose-slate max-w-none subject-markdown">{!! $subject->renderBody() !!}</div>
    </article>

    @if($subject->images->count() > 0)
        <section class="bg-white rounded-lg border border-slate-200 p-6 mb-8">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Galerie</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($subject->images as $image)
                    <figure class="subject-figure rounded-lg border border-slate-200 overflow-hidden">
                        <a href="{{ $image->url() }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ $image->url() }}" alt="{{ $image->alt }}" class="subject-gallery-image">
                        </a>
                        @if($image->alt)
                            <figcaption class="text-xs text-slate-500 p-2 truncate">{{ $image->alt }}</figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    @can('update', $subject)
        <div class="flex flex-wrap items-center gap-3 mb-8">
            <a href="{{ route('subjects.edit', $subject->slug) }}" class="inline-block bg-slate-800 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-900 transition">Modifier le document</a>
            <a href="{{ route('subjects.images.index', $subject->slug) }}" class="inline-block bg-slate-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-800 transition">Galerie</a>

            @if($subject->status === 'draft')
                <form method="POST" action="{{ route('subjects.publish', $subject->slug) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-block bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">Publier</button>
                </form>
            @endif

            <form method="POST" action="{{ route('subjects.destroy', $subject->slug) }}" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" onclick="return confirm('Confirmer la suppression ?')" class="text-red-600 text-sm hover:text-red-800 transition">Supprimer</button>
            </form>
        </div>
    @endcan

    <section id="comments" class="bg-slate-50 rounded-lg border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Discussion</h2>

        @forelse($subject->comments as $comment)
            <div class="mb-4 pb-4 border-b border-slate-200 last:border-0">
                <div class="flex items-center gap-2 text-sm mb-1">
                    <span class="inline-block w-2 h-2 rounded-full" style="background-color: {{ $comment->user->color ?: '#64748b' }}"></span>
                    <span class="font-medium text-slate-900">{{ $comment->user->name }}</span>
                    <span class="text-slate-400 text-xs">{{ $comment->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <p class="text-slate-700 text-sm">{{ $comment->body }}</p>
            </div>
        @empty
            <p class="text-slate-500 text-sm italic mb-4">Aucun commentaire pour le moment. Soyez le premier à partager vos idées !</p>
        @endforelse

        <form method="POST" action="{{ route('subjects.comments.store', $subject->slug) }}" class="mt-4">
            @csrf
            <label for="comment" class="block text-sm font-medium text-slate-700 mb-1">Votre contribution</label>
            <textarea id="comment" name="body" rows="3" maxlength="5000" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">{{ old('body') }}</textarea>
            @error('body')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
            <button type="submit" class="mt-2 bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">Commenter</button>
        </form>
    </section>

</div>

<style>
.subject-document { font-size: 1.125rem; }
.subject-document h2 { font-size: 1.5rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.75rem; }
.subject-document h3 { font-size: 1.25rem; font-weight: 600; margin-top: 1.25rem; margin-bottom: 0.5rem; }
.subject-document ul { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 1rem; }
.subject-document p { margin-bottom: 1rem; }
.subject-document a { color: #059669; text-decoration: underline; }
.subject-document blockquote { border-left: 4px solid #10b981; padding-left: 1rem; margin: 1rem 0; color: #475569; font-style: italic; }
.subject-document table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
.subject-document th, .subject-document td { border: 1px solid #cbd5e1; padding: 0.5rem 0.75rem; text-align: left; }
.subject-document th { background-color: #f1f5f9; font-weight: 600; }
.subject-markdown img { border-radius: 0.5rem; border: 1px solid #e2e8f0; max-width: 100%; height: auto; }
.subject-figure { margin: 1rem 0; }
.subject-figure img { border-radius: 0.5rem; border: 1px solid #e2e8f0; }
.subject-gallery-image { width: 100%; height: 10rem; object-fit: cover; display: block; }
.subject-figure figcaption { font-size: 0.875rem; color: #64748b; margin-top: 0.5rem; }
</style>
@endsection
