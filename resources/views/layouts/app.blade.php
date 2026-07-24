<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'La Main a la Pate') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
    @stack('head')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    @include('layouts.navbar')

    @if(session('success'))
        <div class="max-w-5xl mx-auto px-4 mt-4">
            <div class="bg-green-50 text-green-800 px-4 py-3 rounded-lg border border-green-200 text-sm">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <main class="flex-grow w-full">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer class="bg-white border-t border-slate-200 mt-auto">
        <div class="max-w-5xl mx-auto px-4 py-6 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} La Main a la Pate — Le Rozier
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
