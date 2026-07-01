@extends('layouts.public')

@section('title', 'Nouveau sujet — La Main à la Pâte')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-slate-900 mb-6">Nouveau sujet</h1>

    <form method="POST" action="{{ route('subjects.store') }}" class="bg-white rounded-lg border border-slate-200 p-6">
        @csrf

        <div class="mb-5">
            <label for="theme" class="block text-sm font-medium text-slate-700 mb-1">Thème</label>
            <select id="theme" name="theme" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                @foreach($themes as $theme)
                    <option value="{{ $theme }}" {{ old('theme') === $theme ? 'selected' : '' }}>{{ $theme }}</option>
                @endforeach
            </select>
            @error('theme')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5">
            <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Titre</label>
            <input id="title" name="title" type="text" value="{{ old('title') }}" maxlength="255" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
            @error('title')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5">
            <label class="block text-sm font-medium text-slate-700 mb-1">Document de travail</label>
            <div class="border border-slate-300 rounded-md p-2 mb-2 flex flex-wrap gap-2 bg-slate-50">
                <button type="button" data-cmd="formatBlock" data-arg="h2" class="toolbar-btn toolbar-h2 px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Titre principal</button>
                <button type="button" data-cmd="formatBlock" data-arg="h3" class="toolbar-btn toolbar-h3 px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Sous-titre</button>
                <button type="button" data-cmd="bold" class="toolbar-btn toolbar-bold px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100 font-bold">Gras</button>
                <button type="button" data-cmd="italic" class="toolbar-btn toolbar-italic px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100 italic">Italique</button>
                <button type="button" data-cmd="insertUnorderedList" class="toolbar-btn toolbar-list px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Liste à puces</button>
                <button type="button" data-cmd="insertQuote" class="toolbar-btn toolbar-quote px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Citation</button>
                <button type="button" data-cmd="insertTable" class="toolbar-btn toolbar-table px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Tableau</button>
                <button type="button" data-cmd="insertImage" class="toolbar-btn toolbar-image px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Image</button>
                <button type="button" data-cmd="createLink" class="toolbar-btn toolbar-link px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Lien</button>
            </div>
            <div id="editor" contenteditable="true" data-placeholder="Cliquez ici pour rédiger le document..." class="editor-contenteditable w-full min-h-[200px] border border-slate-300 rounded-md px-3 py-2 text-sm prose max-w-none">{!! old('body') !!}</div>
            <textarea id="body" name="body" hidden>{{ old('body') }}</textarea>
            @error('body')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">Créer en brouillon</button>
            <a href="{{ route('subjects.index') }}" class="text-slate-500 text-sm hover:text-slate-800 transition">Annuler</a>
        </div>
    </form>
</div>

<style>
    .editor-contenteditable:empty::before,
    .editor-contenteditable.is-empty::before {
        content: attr(data-placeholder);
        color: #94a3b8;
        pointer-events: none;
    }
    .editor-contenteditable.is-focused {
        outline: 2px solid #10b981;
        outline-offset: -2px;
    }
    .toolbar-btn.toolbar-h2 {
        font-weight: 700;
        font-size: 0.85rem;
    }
    .toolbar-btn.toolbar-h3 {
        font-weight: 600;
        font-size: 0.78rem;
    }
    .toolbar-btn.toolbar-bold {
        font-weight: 800;
    }
    .toolbar-btn.toolbar-italic {
        font-style: italic;
    }
    .toolbar-active,
    .toolbar-btn.toolbar-active {
        background-color: #0d9488 !important;
        color: #fff !important;
        border-color: #115e59 !important;
        font-weight: 600 !important;
    }
    .toolbar-btn.toolbar-active:hover {
        background-color: #115e59 !important;
        border-color: #134e4a !important;
    }
</style>
@endsection
