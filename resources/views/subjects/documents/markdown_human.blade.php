@extends('layouts.public')

@section('title', ($document->title ?: 'Document') . ' — La Main à la Pâte')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <a href="{{ route('subjects.show', $subject->slug) }}#documents" class="text-sm text-slate-500 hover:text-slate-900 mb-4 inline-block">
        ← Retour au dossier
    </a>

    <article class="bg-white rounded-lg border border-slate-200 p-6 subject-document">
        <header class="mb-6 border-b border-slate-200 pb-4">
            <span class="text-xs px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 border border-blue-200 uppercase tracking-wide font-medium">
                DOSSIER DOCUMENTAIRE
            </span>
            <h1 class="text-xl font-bold text-slate-900 mt-2 leading-snug">
                {{ $document->title ?: $document->filename }}
            </h1>
            @if($document->description)
                <p class="text-sm text-slate-600 mt-2">{{ $document->description }}</p>
            @endif
            <div class="text-xs text-slate-500 mt-3 space-y-1">
                @if($document->author)
                    <p><span class="font-medium">Auteur(s) :</span> {{ $document->author }}</p>
                @endif
                @if($document->document_date)
                    <p><span class="font-medium">Date :</span> {{ $document->document_date->format('d/m/Y') }}</p>
                @endif
            </div>
        </header>

        <div class="prose prose-slate max-w-none text-slate-800 markdown-body">
            {!! $htmlBody !!}
        </div>

        @if($document->source_sha256)
            <footer class="mt-8 pt-4 border-t border-slate-200 text-xs text-slate-400">
                <p>Source SHA-256 : <code class="text-slate-500">{{ $document->source_sha256 }}</code></p>
            </footer>
        @endif
    </article>
</div>
@endsection
