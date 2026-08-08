@extends('layouts.public')

@section('title', 'Espace sujets — La Main à la Pâte')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div class="min-w-0">
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Espace sujets</h1>
            <p class="text-slate-500 text-sm mt-1">Documents de travail et discussion par thème.</p>
        </div>
        @can('create', App\Models\Subject::class)
            <a href="{{ route('subjects.create') }}" class="inline-flex items-center justify-center bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">
                + Nouveau sujet
            </a>
        @endcan
    </div>

    {{-- Grille des thèmes --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
        @foreach($categories as $category)
            <a href="{{ $activeCategory && $activeCategory->id === $category->id ? route('subjects.index') : route('subjects.index', ['theme' => $category->slug]) }}"
               class="rounded-lg border p-3 text-center hover:shadow-md transition flex flex-col items-center justify-center gap-1
                      {{ $activeCategory && $activeCategory->id === $category->id ? 'border-slate-800 ring-2 ring-slate-800' : 'border-slate-200' }}"
               style="background-color: {{ $category->color }}26">
                <span class="text-sm font-semibold text-slate-800 leading-tight">{{ $category->name }}</span>
                <span class="text-xs text-slate-600">{{ $category->subjects_count ?? 0 }} sujet{{ ($category->subjects_count ?? 0) > 1 ? 's' : '' }}</span>
            </a>
        @endforeach
    </div>

    @if($activeCategory)
        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('subjects.index') }}" class="text-sm text-slate-600 hover:text-slate-900 transition">← Tous les thèmes</a>
            <span class="text-sm font-medium text-slate-800">{{ $activeCategory->name }}</span>
        </div>
    @endif

    @if($subjects->count() === 0)
        <div class="text-center py-16 bg-white rounded-lg border border-slate-200">
            <div class="text-4xl mb-3">📝</div>
            <p class="text-slate-500">Aucun sujet pour le moment.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4">
            @foreach($subjects as $subject)
                <article class="bg-white rounded-lg border border-slate-200 p-5 hover:border-slate-300 transition">
                    <div class="flex items-center gap-2 text-xs mb-2 flex-wrap">
                        @if($subject->subCategory)
                            <span class="px-2 py-0.5 rounded-full text-slate-800 font-medium" style="background-color: {{ $subject->subCategory->color ?? $subject->category->color ?? '#e2e8f0' }}40">
                                {{ $subject->subCategory->name }}
                            </span>
                        @elseif($subject->category)
                            <span class="px-2 py-0.5 rounded-full text-slate-800 font-medium" style="background-color: {{ $subject->category->color ?? '#e2e8f0' }}40">
                                {{ $subject->theme }}
                            </span>
                        @elseif($subject->theme)
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $subject->theme }}</span>
                        @endif
                        @if($subject->status === 'draft')
                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Brouillon</span>
                        @endif

                        @if(auth()->check() && auth()->user()->isModeratorOrAdmin())
                            @php
                                $citizenColor = \App\Models\Subject::statusColor($subject->citizen_status);
                                $publicColor = \App\Models\Subject::statusColor($subject->public_status);
                            @endphp
                            <span class="px-2 py-0.5 rounded-full bg-{{ $citizenColor }}-100 text-{{ $citizenColor }}-700 border border-{{ $citizenColor }}-200" title="Version citoyenne">Citoyen : {{ \App\Models\Subject::statusLabel($subject->citizen_status) }}</span>
                            <span class="px-2 py-0.5 rounded-full bg-{{ $publicColor }}-100 text-{{ $publicColor }}-700 border border-{{ $publicColor }}-200" title="Version publique">Public : {{ \App\Models\Subject::statusLabel($subject->public_status) }}</span>
                        @endif
                        @if(auth()->check() && $subject->hasNewVersionFor(auth()->user()))
                            <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 font-semibold">Mis à jour</span>
                        @endif
                        <span class="text-slate-400">{{ $subject->created_at->format('d/m/Y') }}</span>
                    </div>
                    <h2 class="text-lg font-semibold text-slate-900 mb-2">
                        <a href="{{ route('subjects.show', $subject->slug) }}" class="hover:underline">{{ $subject->title }}</a>
                    </h2>
                    <p class="text-slate-600 text-sm line-clamp-2">{{ $subject->summaryFor(auth()->user()) }}</p>
                    <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                        <span class="inline-block w-2 h-2 rounded-full" style="background-color: {{ $subject->user->color ?: '#64748b' }}"></span>
                        {{ $subject->user->name }}
                        <span class="ml-3">{{ $subject->comments->count() }} commentaire{{ $subject->comments->count() > 1 ? 's' : '' }}</span>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $subjects->links() }}</div>
    @endif
</div>
@endsection
