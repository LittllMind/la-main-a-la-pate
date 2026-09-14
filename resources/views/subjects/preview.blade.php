@extends('layouts.public')

@section('title', 'Aperçu ' . $level->label() . ' — ' . $subject->title . ' — La Main à la Pâte')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    {{-- Bandeau d'aperçu --}}
    <div class="bg-{{ $level->color() }}-100 border border-{{ $level->color() }}-300 rounded-lg p-4 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="text-2xl">👁</span>
            <div>
                <p class="font-semibold text-{{ $level->color() }}-900">
                    APERCU ADMINISTRATEUR - {{ strtoupper($level->label()) }}
                    <span class="hidden" data-preview-marker="PREVIEW_BANNER_{{ strtoupper($level->label()) }}"></span>
                </p>
                <p class="text-sm text-{{ $level->color() }}-800">
                    Vous voyez ce que verrait un visiteur de niveau <strong>{{ $level->label() }}</strong>.
                    @if($level === \App\Models\VisibilityLevel::Public)
                        Statut de la version Public : <span class="font-medium">{{ \App\Models\Subject::statusLabel($subject->public_status) }}</span>.
                    @else
                        Statut de la version Citoyen : <span class="font-medium">{{ \App\Models\Subject::statusLabel($subject->citizen_status) }}</span>.
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('subjects.edit', $subject->slug) }}" class="text-sm px-3 py-1.5 rounded bg-white border border-{{ $level->color() }}-300 text-{{ $level->color() }}-900 hover:bg-{{ $level->color() }}-50 transition">
                Modifier le sujet
            </a>
            <a href="{{ route('subjects.show', $subject->slug) }}" class="text-sm px-3 py-1.5 rounded bg-white border border-{{ $level->color() }}-300 text-{{ $level->color() }}-900 hover:bg-{{ $level->color() }}-50 transition">
                Retour à la fiche
            </a>
        </div>
    </div>

    <div class="mb-6">
        <a href="{{ route('subjects.show', $subject->slug) }}" class="text-sm text-slate-500 hover:text-slate-900 mb-2 inline-block">← Retour à la fiche</a>
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

    @php
        $isCitizenLevel = $level === \App\Models\VisibilityLevel::Citizen;
        $wClass = $isCitizenLevel ? 'citizen-document' : 'subject-document';
        $stripTitle = $isCitizenLevel;
        $bodyMarkdown = $subject->bodyAtLevel($level) ?? ($isCitizenLevel ? ($subject->citizen_body ?? '') : $subject->body);
    @endphp
    @include('subjects._content', ['wrapperClass' => $wClass, 'stripTitleH1' => $stripTitle, 'bodyMarkdown' => $bodyMarkdown])

    @if($level === \App\Models\VisibilityLevel::Public)
        <a href="{{ route('subjects.preview', [$subject->slug, 'citizen']) }}" target="_blank" class="inline-block mb-8 bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition">👁 Voir comme Citoyen</a>
    @else
        <a href="{{ route('subjects.preview', [$subject->slug, 'public']) }}" target="_blank" class="inline-block mb-8 bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">👁 Voir comme Public</a>
    @endif
</div>
@endsection

@section('styles')
<style>
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

/* TABLE */
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
@endsection
