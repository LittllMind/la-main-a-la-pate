@extends('layouts.public')

@section('title', 'Plan du site — La Main a la Pate')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-2">Plan du site</h1>
        <p class="text-slate-500 text-sm">Arborescence des sections et acces rapides.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($sections as $section)
            @php
                $color = $section['color'];
                $bgClass = match($color) {
                    'emerald' => 'bg-emerald-50 border-emerald-100',
                    'indigo' => 'bg-indigo-50 border-indigo-100',
                    'amber' => 'bg-amber-50 border-amber-100',
                    'sky' => 'bg-sky-50 border-sky-100',
                    'rose' => 'bg-rose-50 border-rose-100',
                    'slate' => 'bg-slate-100 border-slate-200',
                    default => 'bg-slate-50 border-slate-100',
                };
                $titleClass = match($color) {
                    'emerald' => 'text-emerald-800',
                    'indigo' => 'text-indigo-800',
                    'amber' => 'text-amber-800',
                    'sky' => 'text-sky-800',
                    'rose' => 'text-rose-800',
                    'slate' => 'text-slate-800',
                    default => 'text-slate-800',
                };
                $linkClass = match($color) {
                    'emerald' => 'text-emerald-700 hover:text-emerald-900 hover:bg-emerald-100',
                    'indigo' => 'text-indigo-700 hover:text-indigo-900 hover:bg-indigo-100',
                    'amber' => 'text-amber-700 hover:text-amber-900 hover:bg-amber-100',
                    'sky' => 'text-sky-700 hover:text-sky-900 hover:bg-sky-100',
                    'rose' => 'text-rose-700 hover:text-rose-900 hover:bg-rose-100',
                    'slate' => 'text-slate-700 hover:text-slate-900 hover:bg-slate-200',
                    default => 'text-slate-700 hover:text-slate-900 hover:bg-slate-100',
                };
            @endphp

            <div class="rounded-xl border {{ $bgClass }} overflow-hidden">
                <div class="px-5 py-4 border-b border-black/5 flex items-center gap-2">
                    <span class="text-xl">{{ $section['icon'] }}</span>
                    <h2 class="font-semibold {{ $titleClass }}">{{ $section['label'] }}</h2>
                </div>
                <ul class="p-3 space-y-1">
                    @foreach($section['items'] as $item)
                        <li>
                            @if(str_starts_with($item['route'], '#'))
                                <span class="block px-3 py-2 rounded-md text-sm text-slate-500">{{ $item['label'] }}</span>
                            @else
                                <a href="{{ $item['route'] }}" class="block px-3 py-2 rounded-md text-sm {{ $linkClass }} transition">
                                    {{ $item['label'] }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>
@endsection
