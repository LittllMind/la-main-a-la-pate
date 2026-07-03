@extends('layouts.admin')

@section('title', 'Panneau administrateur')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Panneau d'administration</h1>
        <p class="text-slate-600 text-sm">Accès rapide à toutes les actions de gestion du site.</p>
    </div>

    @foreach($sections as $heading => $cards)
        <section class="mb-10">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-4 border-b border-slate-200 pb-2">{{ $heading }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($cards as $card)
                    <div class="bg-white rounded-xl border border-slate-200 p-6 hover:border-emerald-400 hover:shadow-sm transition group flex flex-col">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-3 bg-{{ $card['color'] }}-50 rounded-lg text-{{ $card['color'] }}-600">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" />
                                </svg>
                            </div>
                            <a href="{{ $card['route'] }}" class="text-xs font-medium text-slate-400 bg-slate-50 px-2 py-1 rounded hover:bg-emerald-50 hover:text-emerald-700 transition">Ouvrir</a>
                        </div>
                        <h3 class="font-semibold text-slate-900 group-hover:text-emerald-700 text-lg">{{ $card['title'] }}</h3>
                        <p class="text-sm text-slate-500 mt-1 mb-4 flex-grow">{{ $card['description'] }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($card['actions'] as $action)
                                <a href="{{ $action['route'] }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium bg-slate-100 text-slate-700 hover:bg-emerald-100 hover:text-emerald-800 transition">
                                    {{ $action['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
@endsection
