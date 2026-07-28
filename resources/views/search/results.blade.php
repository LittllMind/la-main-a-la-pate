@extends('layouts.public')

@section('title', 'Recherche — La Main a la Pate')

@section('content')
    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-900 mb-2">Recherche</h1>
            <form action="{{ route('search') }}" method="GET" class="flex gap-2">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher un sujet, un document..." minlength="2"
                       class="flex-1 rounded-md border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                       autofocus required>
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-md hover:bg-emerald-700 transition">
                    Rechercher
                </button>
            </form>
        </div>

        @if(request('q'))
            @if($subjects->isEmpty() && $documents->isEmpty())
                <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500 text-sm">
                    Aucun resultat pour "{{ request('q') }}"
                </div>
            @else
                @if($subjects->isNotEmpty())
                    <div class="mb-8">
                        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">Sujets ({{ $subjects->count() }})</h2>
                        <div class="space-y-2">
                            @foreach($subjects as $subject)
                                <a href="{{ route('subjects.show', $subject) }}" class="block bg-white rounded-xl border border-slate-200 p-4 hover:bg-slate-50 transition">
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="font-medium text-slate-900">{{ $subject->title }}</span>
                                        <span class="text-xs shrink-0 px-2 py-0.5 rounded-full {{ $subject->status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $subject->status === 'published' ? 'Publie' : 'Brouillon' }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1">par {{ $subject->user->name }} — {{ $subject->created_at->format('d/m/Y') }}</div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($documents->isNotEmpty())
                    <div>
                        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">Documents ({{ $documents->count() }})</h2>
                        <div class="space-y-2">
                            @foreach($documents as $doc)
                                <a href="{{ $doc->url() }}" class="flex items-center gap-3 bg-white rounded-xl border border-slate-200 p-4 hover:bg-slate-50 transition">
                                    <span class="text-xl">{{ $doc->icon() }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-slate-900 truncate">{{ $doc->title ?: $doc->filename }}</div>
                                        @if($doc->description)
                                            <div class="text-xs text-slate-500 truncate">{{ $doc->description }}</div>
                                        @endif
                                    </div>
                                    <span class="text-xs text-slate-500 shrink-0">{{ $doc->humanSize() }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        @endif
    </div>
@endsection
