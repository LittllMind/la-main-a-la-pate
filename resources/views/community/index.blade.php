@extends('layouts.public')

@section('title', 'Communaute')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-slate-900 mb-2">Espaces de discussion</h1>
    <p class="text-slate-500 mb-8">Echangez avec vos voisins dans nos forums thematiques.</p>

    <div class="grid gap-4">
        @foreach($spaces as $space)
        <a href="{{ route('community.show', $space->slug) }}" class="block bg-white rounded-lg border border-slate-200 p-5 hover:border-slate-300 hover:shadow-sm transition">
            <div class="flex items-start gap-4">
                <div class="text-3xl">{{ $space->icon }}</div>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $space->name }}</h2>
                    <p class="text-slate-600 text-sm mt-1">{{ $space->description }}</p>
                    <div class="text-xs text-slate-400 mt-2">
                        {{ $space->topics_count ?? $space->topics()->count() }} sujets
                    </div>
                </div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endsection
