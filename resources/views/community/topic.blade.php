@extends('layouts.public')

@section('title', $topic->title)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <a href="{{ route('community.show', $space->slug) }}" class="text-sm text-slate-500 hover:text-slate-900 mb-4 inline-block">&larr; {{ $space->name }}</a>

    <div class="bg-white rounded-lg border border-slate-200 p-4 sm:p-6 mb-4">
        <h1 class="text-lg sm:text-xl font-bold text-slate-900 mb-2">{{ $topic->title }}</h1>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-4">
            <span class="font-medium text-slate-700">{{ $topic->user->displayName() }}</span>
            <span>·</span>
            <span>{{ $topic->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="prose prose-slate max-w-none text-slate-700 text-sm leading-relaxed">
            {!! nl2br(e($topic->body)) !!}
        </div>
    </div>

    <div class="space-y-3 mb-8">
        @foreach($replies as $reply)
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
                <span class="font-medium text-slate-700">{{ $reply->user->displayName() }}</span>
                <span>·</span>
                <span>{{ $reply->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="text-sm text-slate-700 leading-relaxed">
                {!! nl2br(e($reply->body)) !!}
            </div>
        </div>
        @endforeach
    </div>

    @auth
    <div class="bg-white rounded-lg border border-slate-200 p-6">
        <h3 class="font-semibold text-slate-900 mb-4">Repondre</h3>
        <form method="POST" action="{{ route('community.reply.store', [$space->slug, $topic->slug]) }}">
            @csrf
            <div class="mb-4">
                <textarea name="body" rows="3" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900" placeholder="Votre message..."></textarea>
            </div>
            <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-md text-sm hover:bg-slate-700">Repondre</button>
        </form>
    </div>
    @else
    <div class="text-center bg-slate-100 rounded-lg p-6">
        <p class="text-slate-600 text-sm">Connectez-vous pour repondre.</p>
        <a href="/login" class="inline-block mt-2 text-slate-900 font-medium underline">Se connecter</a>
    </div>
    @endauth
</div>
@endsection
