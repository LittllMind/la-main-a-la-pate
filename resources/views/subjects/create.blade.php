@extends('layouts.public')

@section('title', 'Nouveau sujet — La Main à la Pâte')

@section('content')<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-slate-900 mb-6">Nouveau sujet</h1>

    <form method="POST" action="{{ route('subjects.store') }}" class="bg-white rounded-lg border border-slate-200 p-6">
        @csrf

        <div class="mb-5">
            <label for="theme" class="block text-sm font-medium text-slate-700 mb-1">Thème</label>
            <select id="theme" name="theme" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm" data-theme-toggle>
                <option value="__new__" {{ old('theme') === '__new__' ? 'selected' : '' }}>+ Autre thème</option>
                @foreach($themes as $theme)
                    <option value="{{ $theme }}" {{ old('theme') === $theme ? 'selected' : '' }}>{{ $theme }}</option>
                @endforeach
            </select>
            @error('theme')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5 {{ old('theme') !== '__new__' ? 'hidden' : '' }}" id="theme-other-wrapper">
            <label for="theme_other" class="block text-sm font-medium text-slate-700 mb-1">Nom du nouveau thème</label>
            <input id="theme_other" name="theme_other" type="text" value="{{ old('theme_other') }}" maxlength="120" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
            @error('theme_other')
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

        <div class="mb-5" data-markdown-editor>
            <label class="block text-sm font-medium text-slate-700 mb-1">Document de travail (Markdown)</label>

            <div class="border border-slate-300 rounded-md p-2 mb-2 flex flex-wrap gap-2 bg-slate-50">
                <button type="button" data-insert="## " title="Titre principal" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Titre</button>
                <button type="button" data-insert="### " class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Sous-titre</button>
                <button type="button" data-insert="**texte**" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Gras</button>
                <button type="button" data-insert="*texte*" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Italique</button>
                <button type="button" data-insert="\n- item\n" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Liste</button>
                <button type="button" data-insert="\n\u003e " class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Citation</button>
                <button type="button" data-insert="\n| Col | Col |\n| --- | --- |\n|     |     |\n" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Tableau</button>
                <button type="button" data-insert="[texte](https://)" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Lien</button>
                <button type="button" data-insert="\n![legende](https://)\n" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Image URL</button>
            </div>

            <textarea id="body" name="body" rows="16" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm font-mono" placeholder="Rédigez en Markdown...">{{ old('body') }}</textarea>

            <div class="mt-4">
                <div class="text-xs font-medium text-slate-500 flex items-center gap-2 mb-2">
                    <span>Aperçu</span>
                    <button type="button" id="toggle-preview" class="text-emerald-700">Masquer</button>
                </div>
                <div id="preview" class="prose prose-slate max-w-none border border-slate-200 rounded-md p-4 min-h-[120px]"></div>
            </div>

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
@endsection
