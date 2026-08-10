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

    @include('subjects._content')

    @if($level === \App\Models\VisibilityLevel::Public)
        <a href="{{ route('subjects.preview', [$subject->slug, 'citizen']) }}" target="_blank" class="inline-block mb-8 bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition">👁 Voir comme Citoyen</a>
    @else
        <a href="{{ route('subjects.preview', [$subject->slug, 'public']) }}" target="_blank" class="inline-block mb-8 bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">👁 Voir comme Public</a>
    @endif
</div>
@endsection
