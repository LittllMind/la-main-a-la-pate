@extends('layouts.public')

@section('title', 'La Séraphothèque — Le Rozier')

@section('content')

{{-- HERO --}}
<section
    class="relative w-full min-h-[70vh] sm:min-h-[75vh] lg:min-h-[80vh] flex items-center justify-center overflow-hidden"
    aria-labelledby="hero-title"
>
    <picture class="absolute inset-0">
        <source media="(max-width: 640px)" srcset="{{ $hero['image']['mobile'] }}" type="image/webp">
        <source media="(min-width: 641px)" srcset="{{ $hero['image']['desktop'] }}" type="image/webp">
        <img
            src="{{ $hero['image']['desktop'] }}"
            alt="{{ $hero['image']['alt'] }}"
            class="absolute inset-0 w-full h-full object-cover"
            width="1920" height="1080"
            fetchpriority="high"
            decoding="async"
        >
    </picture>

    {{-- Overlay sombre + gradient pour lisibilité --}}
    <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/70"></div>

    <div class="relative z-10 w-full max-w-4xl mx-auto px-4 sm:px-6 text-center text-white">
        <p class="text-sm sm:text-base tracking-[0.2em] uppercase opacity-90 mb-3 sm:mb-4">
            {{ $hero['subtitle'] }}
        </p>
        <h1 id="hero-title" class="text-4xl sm:text-5xl md:text-7xl font-extrabold tracking-tight uppercase mb-5 sm:mb-6">
            {{ $hero['title'] }}
        </h1>
        <p class="text-lg sm:text-xl md:text-2xl font-medium leading-relaxed max-w-3xl mx-auto mb-4">
            {{ $hero['intro'] }}
        </p>
        <p class="text-sm sm:text-base md:text-lg opacity-90 max-w-2xl mx-auto mb-6">
            {{ $hero['location'] }}
        </p>

        <address class="not-italic text-sm sm:text-base opacity-90 mb-8">
            {{ $hero['address'] }}<br class="sm:hidden">
            {{ $hero['zip_city'] }} · {{ $hero['hours'] }}
        </address>

        <a
            href="{{ $civic['cta_primary']['url'] }}"
            class="inline-block bg-white text-slate-900 px-6 py-3 rounded-md text-sm sm:text-base font-semibold hover:bg-slate-100 transition"
        >
            {{ $civic['cta_primary']['label'] }}
        </a>
    </div>
</section>

{{-- DÉCOUVREZ --}}
<section class="w-full bg-white py-16 sm:py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="text-center max-w-3xl mx-auto mb-12 sm:mb-16">
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-900 mb-4">
                {{ $univers['title'] }}
            </h2>
            <p class="text-slate-600 text-base sm:text-lg leading-relaxed">
                {{ $univers['lead'] }}
            </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 mb-16">
            @foreach ($univers['items'] as $item)
                <a href="#{{ $item['id'] }}" class="group block text-center rounded-xl border border-slate-200 p-5 sm:p-6 hover:shadow-md hover:border-slate-300 transition">
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 mb-2 group-hover:text-indigo-700 transition">
                        {{ $item['name'] }}
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                        {{ $item['title'] }}
                    </p>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- UNIVERS --}}
@foreach ($univers['items'] as $index => $item)
<section id="{{ $item['id'] }}" class="w-full py-14 sm:py-20 {{ $index % 2 === 0 ? 'bg-slate-50' : 'bg-white' }}">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex flex-col {{ $index % 2 === 0 ? 'lg:flex-row' : 'lg:flex-row-reverse' }} gap-8 lg:gap-12 items-center">

            {{-- Texte --}}
            <div class="w-full lg:w-2/5">
                <p class="text-xs sm:text-sm font-bold tracking-widest text-indigo-700 uppercase mb-2">
                    {{ $item['name'] }}
                </p>
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-900 mb-5 leading-tight">
                    {{ $item['title'] }}
                </h2>
                @foreach ($item['text'] as $paragraph)
                    <p class="text-slate-600 leading-relaxed mb-4">
                        {{ $paragraph }}
                    </p>
                @endforeach
                <p class="text-sm font-medium text-slate-500 mt-4">
                    {{ $item['signature'] ?? '' }}
                </p>
            </div>

            {{-- Photos --}}
            <div class="w-full lg:w-3/5">
                @if ($item['id'] === 'friperie')
                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <div class="col-span-2">
                            <img src="{{ $item['images'][0]['src'] }}" alt="{{ $item['images'][0]['alt'] }}" width="{{ $item['images'][0]['width'] }}" height="{{ $item['images'][0]['height'] }}" class="w-full h-64 sm:h-80 object-cover rounded-lg" loading="lazy" decoding="async">
                        </div>
                        <div>
                            <img src="{{ $item['images'][1]['src'] }}" alt="{{ $item['images'][1]['alt'] }}" width="{{ $item['images'][1]['width'] }}" height="{{ $item['images'][1]['height'] }}" class="w-full h-40 sm:h-52 object-cover rounded-lg" loading="lazy" decoding="async">
                        </div>
                        <div>
                            <img src="{{ $item['images'][2]['src'] }}" alt="{{ $item['images'][2]['alt'] }}" width="{{ $item['images'][2]['width'] }}" height="{{ $item['images'][2]['height'] }}" class="w-full h-40 sm:h-52 object-cover rounded-lg" loading="lazy" decoding="async">
                        </div>
                    </div>
                @elseif ($item['id'] === 'brocante')
                    <div class="grid grid-cols-12 gap-3 sm:gap-4">
                        <div class="col-span-7 row-span-2">
                            <img src="{{ $item['images'][0]['src'] }}" alt="{{ $item['images'][0]['alt'] }}" width="{{ $item['images'][0]['width'] }}" height="{{ $item['images'][0]['height'] }}" class="w-full h-full min-h-[16rem] sm:min-h-[20rem] object-cover rounded-lg" loading="lazy" decoding="async">
                        </div>
                        <div class="col-span-5">
                            <img src="{{ $item['images'][1]['src'] }}" alt="{{ $item['images'][1]['alt'] }}" width="{{ $item['images'][1]['width'] }}" height="{{ $item['images'][1]['height'] }}" class="w-full h-32 sm:h-40 object-cover rounded-lg" loading="lazy" decoding="async">
                        </div>
                        <div class="col-span-5">
                            <img src="{{ $item['images'][2]['src'] }}" alt="{{ $item['images'][2]['alt'] }}" width="{{ $item['images'][2]['width'] }}" height="{{ $item['images'][2]['height'] }}" class="w-full h-32 sm:h-40 object-cover rounded-lg" loading="lazy" decoding="async">
                        </div>
                        <div class="col-span-12">
                            <img src="{{ $item['images'][3]['src'] }}" alt="{{ $item['images'][3]['alt'] }}" width="{{ $item['images'][3]['width'] }}" height="{{ $item['images'][3]['height'] }}" class="w-full h-40 sm:h-56 object-cover rounded-lg" loading="lazy" decoding="async">
                        </div>
                    </div>
                @elseif ($item['id'] === 'bouquinerie')
                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <div class="col-span-2 sm:col-span-1 sm:row-span-2">
                            <img src="{{ $item['images'][0]['src'] }}" alt="{{ $item['images'][0]['alt'] }}" width="{{ $item['images'][0]['width'] }}" height="{{ $item['images'][0]['height'] }}" class="w-full h-64 sm:h-full min-h-[20rem] object-cover rounded-lg" loading="lazy" decoding="async">
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <img src="{{ $item['images'][1]['src'] }}" alt="{{ $item['images'][1]['alt'] }}" width="{{ $item['images'][1]['width'] }}" height="{{ $item['images'][1]['height'] }}" class="w-full h-40 sm:h-48 object-cover rounded-lg" loading="lazy" decoding="async">
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <img src="{{ $item['images'][2]['src'] }}" alt="{{ $item['images'][2]['alt'] }}" width="{{ $item['images'][2]['width'] }}" height="{{ $item['images'][2]['height'] }}" class="w-full h-40 sm:h-48 object-cover rounded-lg" loading="lazy" decoding="async">
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <div class="col-span-2 sm:col-span-1">
                            <img src="{{ $item['images'][0]['src'] }}" alt="{{ $item['images'][0]['alt'] }}" width="{{ $item['images'][0]['width'] }}" height="{{ $item['images'][0]['height'] }}" class="w-full h-64 sm:h-80 object-cover rounded-lg" loading="lazy" decoding="async">
                        </div>
                        <div class="col-span-2 sm:col-span-1 flex flex-col gap-3 sm:gap-4">
                            <img src="{{ $item['images'][1]['src'] }}" alt="{{ $item['images'][1]['alt'] }}" width="{{ $item['images'][1]['width'] }}" height="{{ $item['images'][1]['height'] }}" class="w-full h-32 sm:h-40 object-cover rounded-lg" loading="lazy" decoding="async">
                            <img src="{{ $item['images'][2]['src'] }}" alt="{{ $item['images'][2]['alt'] }}" width="{{ $item['images'][2]['width'] }}" height="{{ $item['images'][2]['height'] }}" class="w-full h-32 sm:h-40 object-cover rounded-lg" loading="lazy" decoding="async">
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</section>
@endforeach

{{-- VALEURS --}}
<section class="w-full bg-indigo-900 text-white py-14 sm:py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach ($values['items'] as $value)
                <div class="text-center sm:text-left">
                    <h3 class="text-base sm:text-lg font-bold tracking-widest uppercase mb-3">
                        {{ $value['name'] }}
                    </h3>
                    <p class="text-indigo-100 text-sm sm:text-base leading-relaxed">
                        {{ $value['text'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ARTISANAT & CREATIONS --}}
<section class="w-full bg-white py-14 sm:py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12 items-center">
            <div class="w-full lg:w-1/2 grid grid-cols-2 gap-3 sm:gap-4 order-2 lg:order-1">
                @foreach ($artisanat['images'] as $img)
                    <img src="{{ $img['src'] }}" alt="{{ $img['alt'] }}" width="{{ $img['width'] }}" height="{{ $img['height'] }}" class="w-full h-48 sm:h-60 object-cover rounded-lg" loading="lazy" decoding="async">
                @endforeach
            </div>
            <div class="w-full lg:w-1/2 order-1 lg:order-2">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-900 mb-5">
                    {{ $artisanat['title'] }}
                </h2>
                <p class="text-slate-600 text-base sm:text-lg leading-relaxed">
                    {{ $artisanat['text'] }}
                </p>
            </div>
        </div>
    </div>
</section>

{{-- COUPS DE CŒUR --}}
@if (count($favorites['items']) > 0)
<section class="w-full bg-slate-50 py-14 sm:py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-900 text-center mb-10 sm:mb-12">
            {{ $favorites['title'] }}
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($favorites['items'] as $fav)
                <article class="favori-card bg-white rounded-xl overflow-hidden border border-slate-200 hover:shadow-md transition">
                    <div class="h-48 sm:h-56 overflow-hidden">
                        <img src="{{ $fav['image'] }}" alt="{{ $fav['alt'] }}" width="600" height="400" class="w-full h-full object-cover" loading="lazy" decoding="async">
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-slate-900 mb-1">
                            {{ $fav['title'] }}
                        </h3>
                        <p class="text-sm text-slate-500 leading-relaxed">
                            {{ $fav['description'] }}
                        </p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- DEPUIS 2022 --}}
<section class="w-full bg-white py-14 sm:py-20 border-t border-slate-200">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12 items-center">
            <div class="w-full lg:w-1/2">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-900 mb-5">
                    {{ $about['title'] }}
                </h2>
                @foreach ($about['text'] as $paragraph)
                    <p class="text-slate-600 text-base sm:text-lg leading-relaxed mb-4">
                        {{ $paragraph }}
                    </p>
                @endforeach
            </div>
            <div class="w-full lg:w-1/2">
                <img src="{{ $about['image']['src'] }}" alt="{{ $about['image']['alt'] }}" width="{{ $about['image']['width'] }}" height="{{ $about['image']['height'] }}" class="w-full h-64 sm:h-96 object-cover rounded-lg" loading="lazy" decoding="async">
            </div>
        </div>
    </div>
</section>

{{-- INFOS PRATIQUES + RÉSEAUX --}}
<section class="w-full bg-slate-50 py-14 sm:py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-6">
            {{ $practical['title'] }}
        </h2>
        <address class="not-italic text-slate-600 text-base sm:text-lg mb-2">
            {{ $practical['address'] }}<br>
            {{ $practical['zip_city'] }}
        </address>
        <p class="text-slate-600 text-base sm:text-lg mb-8">
            {{ $practical['hours'] }}
        </p>

        <div class="flex flex-col sm:flex-row justify-center gap-4 mb-10">
            <a href="{{ $social['instagram']['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-3 bg-white border border-slate-200 px-5 py-3 rounded-lg hover:border-pink-300 hover:shadow-sm transition">
                <svg class="w-5 h-5 text-pink-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                <span class="font-semibold text-slate-900">{{ $social['instagram']['label'] }}</span>
                <span class="text-slate-500 text-sm">{{ $social['instagram']['handle'] }}</span>
            </a>
            <a href="{{ $social['facebook']['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-3 bg-white border border-slate-200 px-5 py-3 rounded-lg hover:border-blue-300 hover:shadow-sm transition">
                <svg class="w-5 h-5 text-blue-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                <span class="font-semibold text-slate-900">{{ $social['facebook']['label'] }}</span>
                <span class="text-slate-500 text-sm">{{ $social['facebook']['handle'] }}</span>
            </a>
        </div>
    </div>
</section>

{{-- SITUATION CIVIQUE --}}
<section class="w-full bg-slate-100 py-14 sm:py-20">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center">
        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-4">
            {{ $civic['title'] }}
        </h2>
        <p class="text-slate-600 text-base sm:text-lg leading-relaxed mb-8">
            {{ $civic['text'] }}
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ $civic['cta_primary']['url'] }}" class="inline-block bg-slate-900 text-white px-6 py-3 rounded-md font-semibold hover:bg-slate-800 transition">
                {{ $civic['cta_primary']['label'] }}
            </a>
            <a href="{{ $civic['cta_secondary']['url'] }}" target="_blank" rel="noopener" class="inline-block bg-white text-slate-900 border border-slate-300 px-6 py-3 rounded-md font-semibold hover:bg-slate-50 transition">
                {{ $civic['cta_secondary']['label'] }}
            </a>
        </div>
    </div>
</section>

@endsection
