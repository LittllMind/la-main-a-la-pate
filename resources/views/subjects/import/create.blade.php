@extends('layouts.public')

@section('title', 'Importer un sujet — La Main à la Pâte')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Importer un sujet</h1>
        <p class="text-slate-600 text-sm">Archive ZIP contenant un fichier Markdown (.md) et les images référencées.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 text-emerald-800 rounded-md text-sm">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('subjects.import.store') }}" enctype="multipart/form-data" class="bg-white rounded-lg border border-slate-200 p-6">
        @csrf

        <div class="mb-5">
            <label for="archive" class="block text-sm font-medium text-slate-700 mb-1">Archive ZIP</label>
            <input id="archive" name="archive" type="file" accept=".zip" class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
            @error('archive')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5">
            <label for="theme" class="block text-sm font-medium text-slate-700 mb-1">Thème</label>
            <select id="theme" name="theme" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm" data-theme-toggle>
                <option value="__new__">+ Autre thème</option>
                @foreach(collect(['Séraphothèque','Urbanisme','Mémoire','Nature','Vie du village'])->sort() as $theme)
                    <option value="{{ $theme }}" {{ old('theme') === $theme ? 'selected' : '' }}>{{ $theme }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-5 hidden" id="theme-other-wrapper">
            <label for="theme_other" class="block text-sm font-medium text-slate-700 mb-1">Nom du nouveau thème</label>
            <input id="theme_other" name="theme_other" type="text" value="{{ old('theme_other') }}" maxlength="120" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
        </div>

        <div class="mb-5">
            <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Titre du sujet</label>
            <input id="title" name="title" type="text" value="{{ old('title') }}" maxlength="255" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
        </div>

        <div class="mb-5">
            <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Statut</label>
            <select id="status" name="status" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Publié</option>
            </select>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">Importer</button>
            <a href="{{ route('subjects.index') }}" class="text-slate-500 text-sm hover:text-slate-800">Annuler</a>
        </div>
    </form>
</div>
@endsection
