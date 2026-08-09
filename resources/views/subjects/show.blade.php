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

        <div class="flex items-center gap-3 mt-4 flex-wrap">
            <a href="{{ route('subjects.pdf.show', $subject->slug) }}" target="_blank" class="inline-block bg-slate-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-800 transition" data-testid="btn-pdf-show">Ouvrir le PDF</a>
            <a href="{{ route('subjects.pdf.download', $subject->slug) }}" class="inline-block bg-slate-100 text-slate-700 border border-slate-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-200 transition" data-testid="btn-pdf-download">Télécharger le PDF</a>
        </div>
    </div>

    <article class="bg-white rounded-lg border border-slate-200 p-6 mb-8 subject-document">
        <div class="prose prose-slate max-w-none subject-markdown">{!! $subject->renderBody() !!}</div>
    </article>

    @if($subject->images->count() > 0)
        <section class="mb-8">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold text-slate-900">Galerie</h2>
                @can('update', $subject)
                    <a href="{{ route('subjects.images.index', $subject->slug) }}" class="text-sm text-emerald-700 hover:text-emerald-900">Gérer les images</a>
                @endcan
            </div>
            <div class="relative group" data-carousel>
                <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory scroll-smooth pb-3" data-carousel-track>
                    @foreach($subject->images->sortBy('position') as $image)
                        <figure class="snap-start shrink-0 w-[85vw] sm:w-[60vw] md:w-[45vw] lg:w-[35vw] rounded-lg border border-slate-200 overflow-hidden bg-white">
                            <a href="{{ $image->url() }}" target="_blank" rel="noopener noreferrer" class="block">
                                <img src="{{ $image->url() }}" alt="{{ $image->alt }}" loading="lazy" class="w-full h-56 sm:h-64 object-cover block">
                            </a>
                            @if($image->alt)
                                <figcaption class="text-xs text-slate-500 p-3 truncate">{{ $image->alt }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
                @if($subject->images->count() > 1)
                    <button type="button" data-carousel-prev class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 border border-slate-200 shadow-sm text-slate-700 hover:bg-white hidden sm:flex items-center justify-center opacity-0 group-hover:opacity-100 transition" aria-label="Image précédente">
                        ‹
                    </button>
                    <button type="button" data-carousel-next class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 border border-slate-200 shadow-sm text-slate-700 hover:bg-white hidden sm:flex items-center justify-center opacity-0 group-hover:opacity-100 transition" aria-label="Image suivante">
                        ›
                    </button>
                @endif
            </div>
            @if($subject->images->count() > 1)
                <p class="text-xs text-slate-400 mt-1 sm:hidden">Glisser pour parcourir les {{ $subject->images->count() }} images</p>
            @endif
        </section>
    @endif

    {{-- Section Documents Sources — visible par tous ceux ayant accès au sujet --}}
    @if($subject->documents->count() > 0)
        <section class="bg-white rounded-lg border border-slate-200 p-6 mb-8">
            <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M6 14h12M3 6h18m-1 14l-4-4h-6l-4 4m-2 0h6"/></svg>
                Sources et documents
            </h2>
            <ul class="space-y-3">
                @foreach($subject->documents as $doc)
                    <li class="flex items-start gap-3 bg-slate-50 rounded-md border border-slate-200 p-3">
                        <span class="text-2xl select-none" title="Extension: {{ $doc->extension() }}">
                            {{ $doc->icon() }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-2">
                                <a href="{{ $doc->url() }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-900 truncate">
                                    {{ $doc->title ?: $doc->filename }}
                                </a>
                                <span class="text-xs text-slate-400 whitespace-nowrap">{{ $doc->humanSize() }}</span>
                                @if($doc->redacted)
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 border border-amber-200" data-redacted-badge>Version expurgée</span>
                                @endif
                            </div>
                            @if($doc->document_date || $doc->document_type || $doc->author || $doc->recipient)
                                <div class="text-xs text-slate-500 mb-1 flex flex-wrap gap-x-3 gap-y-1">
                                    @if($doc->document_date)
                                        <span>Date : {{ $doc->document_date->format('d/m/Y') }}</span>
                                    @endif
                                    @if($doc->document_type)
                                        <span>Nature : {{ $doc->document_type }}</span>
                                    @endif
                                    @if($doc->author)
                                        <span>Auteur : {{ $doc->author }}</span>
                                    @endif
                                    @if($doc->recipient)
                                        <span>Destinataire : {{ $doc->recipient }}</span>
                                    @endif
                                </div>
                            @endif
                            @if($doc->description)
                                <p class="text-xs text-slate-600 mt-0.5">{{ $doc->description }}</p>
                            @endif
                            @if($doc->establishes || $doc->limitations)
                                <div class="text-xs text-slate-600 mt-1 space-y-1">
                                    @if($doc->establishes)
                                        <p><span class="font-medium">Établit :</span> {{ $doc->establishes }}</p>
                                    @endif
                                    @if($doc->limitations)
                                        <p><span class="font-medium">N'établit pas :</span> {{ $doc->limitations }}</p>
                                    @endif
                                </div>
                            @endif
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 uppercase tracking-wide">{{ $doc->category }}</span>
                                <span class="text-xs text-slate-400">Ajouté le {{ $doc->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                        <a href="{{ $doc->url() }}" class="text-sm text-slate-500 hover:text-slate-900 border border-slate-300 rounded-md px-2 py-1 bg-white" download title="Télécharger">
                            Télécharger
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @can('update', $subject)
        <div class="flex flex-wrap items-center gap-3 mb-8">
            <a href="{{ route('subjects.edit', $subject->slug) }}" class="inline-block bg-slate-800 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-900 transition">Modifier le document</a>
            <a href="{{ route('subjects.images.index', $subject->slug) }}" class="inline-block bg-slate-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-800 transition">Galerie</a>

            @if($subject->citizen_status !== 'published' && filled($subject->citizen_body))
                <form method="POST" action="{{ route('subjects.publish.citizen', $subject->slug) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-block bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">Publier aux citoyens</button>
                </form>
            @endif
            @if($subject->citizen_status !== 'hidden')
                <form method="POST" action="{{ route('subjects.hide.citizen', $subject->slug) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-block bg-amber-100 text-amber-800 border border-amber-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-amber-200 transition">Masquer aux citoyens</button>
                </form>
            @endif

            @if($subject->public_status !== 'published' && filled($subject->public_body))
                <form method="POST" action="{{ route('subjects.publish.public', $subject->slug) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-block bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition" onclick="return confirm('Confirmer la publication publique ? Le contenu sera accessible aux visiteurs non connectés.')">Publier au public</button>
                </form>
            @endif
            @if($subject->public_status !== 'hidden')
                <form method="POST" action="{{ route('subjects.hide.public', $subject->slug) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-block bg-amber-100 text-amber-800 border border-amber-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-amber-200 transition">Masquer au public</button>
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

[data-carousel-track]::-webkit-scrollbar { height: 6px; }
[data-carousel-track]::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
[data-carousel-track]::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const carousels = document.querySelectorAll('[data-carousel]');
        carousels.forEach(function (carousel) {
            const track = carousel.querySelector('[data-carousel-track]');
            const prev = carousel.querySelector('[data-carousel-prev]');
            const next = carousel.querySelector('[data-carousel-next]');
            if (!track) return;

            const scrollAmount = track.clientWidth * 0.8;

            if (prev) prev.addEventListener('click', function () { track.scrollBy({ left: -scrollAmount, behavior: 'smooth' }); });
            if (next) next.addEventListener('click', function () { track.scrollBy({ left: scrollAmount, behavior: 'smooth' }); });
        });
    });
</script>
@endsection
