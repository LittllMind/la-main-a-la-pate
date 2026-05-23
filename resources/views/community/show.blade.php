@extends('layouts.public')

@section('title', $space->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <a href="{{ route('community.index') }}" class="text-sm text-slate-500 hover:text-slate-900 mb-4 inline-block">&larr; Tous les espaces</a>

    <div class="flex items-center gap-3 mb-6">
        <div class="text-4xl">{{ $space->icon }}</div>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $space->name }}</h1>
            <p class="text-slate-500 text-sm">{{ $space->description }}</p>
        </div>
    </div>

    <div class="mb-6">
        <a href="{{ route('community.show', $space->slug) }}#new-topic" class="inline-block bg-slate-900 text-white px-4 py-2 rounded-md text-sm hover:bg-slate-700">
            + Nouveau sujet
        </a>
    </div>

    <div class="space-y-3">
        @forelse($topics as $topic)
        <div class="bg-white rounded-lg border border-slate-200 p-4 flex items-center justify-between hover:border-slate-300 transition">
            <div>
                <h2 class="font-semibold text-slate-900">
                    @if($topic->is_pinned)<span class="text-amber-500 text-xs mr-1">EPINGLE</span>@endif
                    <a href="{{ route('community.topic.show', [$space->slug, $topic->slug]) }}" class="hover:underline">
                        {{ $topic->title }}
                    </a>
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    par {{ $topic->user->displayName() }} — {{ $topic->created_at->format('d/m/Y') }}
                    @if($topic->replies_count ?? 0) · {{ $topic->replies_count }} reponses @endif
                    · {{ $topic->view_count }} vues
                </p>
            </div>
        </div>
        @empty
        <div class="text-center text-slate-500 py-12 bg-white rounded-lg border border-slate-200">
            Aucun sujet dans cet espace. Soyez le premier a en creer un !
        </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $topics->links() }}</div>

    @auth
    <div id="new-topic" class="mt-8 bg-white rounded-lg border border-slate-200 p-6">
        <h3 class="font-semibold text-slate-900 mb-4">Nouveau sujet</h3>
        <form method="POST" action="{{ route('community.topic.store', $space->slug) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Titre</label>
                <input type="text" name="title" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Message</label>
                <textarea name="body" rows="4" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"></textarea>
            </div>
            <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-md text-sm hover:bg-slate-700">Publier</button>
        </form>
    </div>
    @else
    <div class="mt-8 text-center bg-slate-100 rounded-lg p-6">
        <p class="text-slate-600 text-sm">Connectez-vous pour participer aux discussions.</p>
        <a href="/login" class="inline-block mt-2 text-slate-900 font-medium underline">Se connecter</a>
    </div>
    @endauth
</div>
@endsection
