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
            {{-- Mes sujets --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-800">Mes sujets</h2>
                    <a href="/sujets" class="text-sm text-emerald-600 hover:underline">Voir tout</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($subjects as $subject)
                        <a href="{{ route('subjects.show', $subject) }}" class="block px-6 py-4 hover:bg-slate-50 transition">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-900">{{ $subject->title }}</span>
                                <span class="text-xs text-slate-500">{{ $subject->status === 'published' ? 'Publie' : 'Brouillon' }}</span>
                            </div>
                            <div class="text-xs text-slate-500 mt-1">{{ $subject->theme }}</div>
                        </a>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-slate-500">
                            Vous n'avez pas encore cree de sujet.
                            <br>
                            <a href="{{ route('subjects.create') }}" class="text-emerald-600 hover:underline mt-2 inline-block">Creer mon premier sujet</a>
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
        <div class="bg-white rounded-xl border border-slate-200 p-6">
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
    </div>
@endsection
