@extends('layouts.public')

@section('title', $subject->title . ' — La Main à la Pâte')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    <div class="mb-6">
        @php
            $isGuest = auth()->guest();
            $backIsSeraphotheque = $isGuest && $subject->theme === 'Séraphothèque';
            $backUrl = $backIsSeraphotheque ? route('seraphotheque') : route('subjects.index');
            $effectiveStatus = $isGuest ? $subject->public_status : $subject->status;
        @endphp
        <a href="{{ $backUrl }}" class="text-sm text-slate-500 hover:text-slate-900 mb-2 inline-block">{{ $backIsSeraphotheque ? '← Retour à la Séraphothèque' : '← Retour aux sujets' }}</a>
        <div class="flex items-center gap-2 text-xs mb-2">
            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $subject->theme }}</span>
            @if($effectiveStatus === 'draft')
                <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Brouillon</span>
            @endif
        </div>
        <h1 class="text-3xl font-bold text-slate-900">{{ $subject->title }}</h1>
        <div class="mt-2 flex items-center gap-2 text-sm text-slate-500">
            <span class="inline-block w-3 h-3 rounded-full" style="background-color: {{ $subject->user->color ?: '#64748b' }}"></span>
            Rédigé par {{ $subject->user->name }} — {{ $subject->updated_at->format('d/m/Y') }}
        </div>

        @if(auth()->check())
            <div class="flex items-center gap-3 mt-4 flex-wrap">
                <a href="{{ route('subjects.pdf.show', $subject->slug) }}" target="_blank" class="inline-block bg-slate-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-800 transition" data-testid="btn-pdf-show">Ouvrir le PDF</a>
                <a href="{{ route('subjects.pdf.download', $subject->slug) }}" class="inline-block bg-slate-100 text-slate-700 border border-slate-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-200 transition" data-testid="btn-pdf-download">Télécharger le PDF</a>
            </div>
        @endif
    </div>

    @include('subjects._content', ['wrapperClass' => 'subject-document', 'stripTitleH1' => false])

    @can('update', $subject)
        <div class="flex flex-wrap items-center gap-3 mb-8">
            <a href="{{ route('subjects.edit', $subject->slug) }}" class="inline-block bg-slate-800 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-900 transition">Modifier le document</a>
            <a href="{{ route('subjects.images.index', $subject->slug) }}" class="inline-block bg-slate-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-800 transition">Galerie</a>
            <a href="{{ route('subjects.preview', [$subject->slug, 'public']) }}" target="_blank" class="inline-block bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">👁 Voir comme Public</a>
            <a href="{{ route('subjects.preview', [$subject->slug, 'citizen']) }}" target="_blank" class="inline-block bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition">👁 Voir comme Citoyen</a>

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

    @if(auth()->check())
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
    @endif

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

/* ============================================================
   CITIZEN DOCUMENT — rendu fiche citoyenne (non specific videoprotection)
   ============================================================ */
.citizen-document { font-size: 1.125rem; line-height: 1.65; color: #334155; }
.citizen-document p   { margin-bottom: 1.25rem; }
.citizen-document h2  { font-size: 1.5rem; font-weight: 700; margin-top: 2.25rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #cbd5e1; }
.citizen-document h3  { font-size: 1.25rem; font-weight: 600; margin-top: 1.75rem; margin-bottom: 0.75rem; color: #475569; }
.citizen-document h4  { font-size: 1.05rem; font-weight: 600; margin-top: 1.25rem; margin-bottom: 0.5rem; color: #475569; }
.citizen-document ul,
.citizen-document ol  { margin: 1rem 0 1.25rem 0; padding-left: 1.75rem; }
.citizen-document li  { margin-bottom: 0.3rem; line-height: 1.6; }
.citizen-document a  { color: #0f766e; text-decoration: underline; text-underline-offset: 3px; }

/* TABLE — vrais espacements, en-têtes visibles */
.citizen-document table { width: 100%; border-collapse: collapse; margin: 1.25rem 0; font-size: 0.9375rem; }
.citizen-document th,
.citizen-document td  { border: 1px solid #cbd5e1; padding: 0.6rem 0.85rem; text-align: left; }
.citizen-document th  { background-color: #f1f5f9; font-weight: 700; color: #334155; }
.citizen-document tr:nth-child(even) { background-color: #f8fafc; }

/* BLOCKQUOTE / L'ESSENTIEL — callout distinct */
.citizen-document blockquote { 
    border-left: 4px solid #0f766e; 
    padding: 1rem 1.25rem; 
    margin: 1.5rem 0; 
    background-color: #f0fdf4; 
    border-radius: 0.375rem; 
    color: #1e293b; 
    font-style: normal; 
}
.citizen-document blockquote p:first-child { margin-top: 0; }
.citizen-document blockquote p:last-child  { margin-bottom: 0; }
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
