@extends('layouts.public')

@section('title', 'Tableau de bord — La Main a la Pate')

@section('content')
    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Tableau de bord</h1>
            <p class="text-slate-600 text-sm">
                Bienvenue, <span class="font-medium text-slate-900">{{ $user->name }}</span>.
                Retrouvez ici vos contributions et vos acces rapides.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Derniers sujets actualises --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-800">Derniers sujets actualises</h2>
                    <a href="{{ route('subjects.index') }}" class="text-sm text-emerald-600 hover:underline">Voir tout</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($recentSubjects as $subject)
                        <a href="{{ route('subjects.show', $subject) }}" class="block px-6 py-4 hover:bg-slate-50 transition">
                            <div class="flex items-start justify-between gap-3">
                                <span class="font-medium text-slate-900 line-clamp-1">{{ $subject->title }}</span>
                                <span class="text-xs shrink-0 px-2 py-0.5 rounded-full {{ $subject->status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $subject->status === 'published' ? 'Publie' : 'Brouillon' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-white text-xs font-semibold shrink-0"
                                          style="background-color: {{ $subject->user->color ?: '#64748b' }}">
                                        {{ strtoupper(substr($subject->user->name, 0, 1)) }}
                                    </span>
                                    <span class="truncate max-w-[12rem]">{{ $subject->user->name }}</span>
                                    @if($subject->category)
                                        <span class="text-slate-300">|</span>
                                        <span class="truncate max-w-[10rem]">{{ $subject->category->name }}</span>
                                    @endif
                                </div>
                                <span class="text-xs text-slate-500 shrink-0">{{ $subject->last_activity_at->diffForHumans() }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-slate-500">
                            Aucun sujet n'a ete recomment mis a jour.
                            <br>
                            <a href="{{ route('subjects.create') }}" class="text-emerald-600 hover:underline mt-2 inline-block">Creer le premier sujet</a>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Mes commentaires --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-800">Mes commentaires recents</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($comments as $comment)
                        <a href="{{ route('subjects.show', $comment->subject) }}" class="block px-6 py-4 hover:bg-slate-50 transition">
                            <div class="text-sm text-slate-700 line-clamp-2">{{ Str::limit(strip_tags($comment->body), 120) }}</div>
                            <div class="text-xs text-slate-500 mt-1">
                                Sur <span class="font-medium">{{ $comment->subject->title }}</span>
                                — {{ $comment->created_at->format('d/m/Y') }}
                            </div>
                        </a>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-slate-500">
                            Vous n'avez pas encore commente de sujet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Acces rapides --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6 mb-8">
            <h2 class="font-semibold text-slate-800 mb-4">Acces rapides</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <a href="/sujets" class="flex items-center gap-3 p-4 rounded-lg border border-slate-100 hover:border-emerald-300 hover:bg-emerald-50 transition">
                    <span class="text-2xl">📚</span>
                    <span class="text-sm font-medium text-slate-700">Sujets du village</span>
                </a>

                @if($user->isAdmin())
                    <a href="{{ route('admin.panel') }}" class="flex items-center gap-3 p-4 rounded-lg border border-slate-100 hover:border-emerald-300 hover:bg-emerald-50 transition">
                        <span class="text-2xl">🛠️</span>
                        <span class="text-sm font-medium text-slate-700">Administration</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Activité récente --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Activité récente</h2>
                @if($user->isAdmin())
                    <a href="{{ route('admin.activity') }}" class="text-sm text-emerald-600 hover:underline">Voir tout</a>
                @endif
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($activity as $log)
                    <div class="px-6 py-4 flex items-start gap-3">
                        <span class="inline-block w-2 h-2 rounded-full mt-1.5" style="background-color: {{ $log->user->color ?: '#64748b' }}"></span>
                        <div class="flex-1">
                            <p class="text-sm text-slate-700">
                                {{ $log->description }}
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                par <span class="font-medium">{{ $log->user->name }}</span>
                                — {{ $log->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-sm text-slate-500">
                        Aucune activité enregistrée pour l'instant.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
