@extends('layouts.public')

@section('title', 'Espace sujets — La Main à la Pâte')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Espace sujets</h1>
            <p class="text-slate-500 text-sm mt-1">Documents de travail et discussion par thème.</p>
        </div>
        @can('create', App\Models\Subject::class)
            <a href="{{ route('subjects.create') }}" class="inline-flex items-center justify-center bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">
                + Nouveau sujet
            </a>
        @endcan
    </div>

    @php
        $user = auth()->user();
    @endphp
    @if($user && $user->isModeratorOrAdmin() && $themes->count() > 0)
        <form method="GET" action="{{ route('subjects.index') }}" class="mb-6 flex flex-wrap items-center gap-3">
            <label for="theme-filter" class="text-sm font-medium text-slate-700">Filtrer par thème :</label>
            <select id="theme-filter" name="theme" class="border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">Tous les thèmes</option>
                @foreach($themes as $theme)
                    <option value="{{ $theme }}" {{ $selectedTheme === $theme ? 'selected' : '' }}>{{ $theme }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-slate-800 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-900 transition">Filtrer</button>
            @if($selectedTheme)
                <a href="{{ route('subjects.index') }}" class="text-slate-500 text-sm hover:text-slate-800 transition">Réinitialiser</a>
            @endif
        </form>
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
                    <div class="flex items-center gap-2 text-xs mb-2">
                        @if($subject->subCategory)
                            <span class="px-2 py-0.5 rounded-full text-white" style="background-color: {{ $subject->subCategory->color ?? '#64748b' }}">
                                {{ $subject->subCategory->name }}
                            </span>
                        @elseif($subject->theme)
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $subject->theme }}</span>
                        @endif
                        @if($subject->status === 'draft')
                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Brouillon</span>
                        @endif
                        <span class="text-slate-400">{{ $subject->created_at->format('d/m/Y') }}</span>
                    </div>
                    <h2 class="text-lg font-semibold text-slate-900 mb-2">
                        <a href="{{ route('subjects.show', $subject->slug) }}" class="hover:underline">{{ $subject->title }}</a>
                    </h2>
                    <p class="text-slate-600 text-sm line-clamp-2">{{ strip_tags($subject->body) }}</p>
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
