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
                <button type="button" data-cmd="formatBlock" data-arg="h2" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Titre 1</button>
                <button type="button" data-cmd="formatBlock" data-arg="h3" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Titre 2</button>
                <button type="button" data-cmd="bold" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100 font-bold">G</button>
                <button type="button" data-cmd="italic" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100 italic">I</button>
                <button type="button" data-cmd="insertUnorderedList" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Liste</button>
                <button type="button" data-cmd="createLink" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Lien</button>
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

<script>
    const editor = document.getElementById('editor');
    const textarea = document.getElementById('body');
    const form = editor.closest('form');

    function exec(cmd, arg = null) {
        document.execCommand(cmd, false, arg);
        editor.focus();
        syncPlaceholder();
    }

    document.querySelectorAll('.toolbar-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const cmd = btn.dataset.cmd;
            const arg = btn.dataset.arg || null;
            if (cmd === 'createLink') {
                const url = prompt('Adresse du lien (https://...)');
                if (url) exec(cmd, url);
            } else {
                exec(cmd, arg);
            }
        });
    });

    function syncPlaceholder() {
        if (editor.innerText.trim().length === 0) {
            editor.classList.add('is-empty');
        } else {
            editor.classList.remove('is-empty');
        }
    }

    editor.addEventListener('input', syncPlaceholder);
    editor.addEventListener('focus', () => editor.classList.add('is-focused'));
    editor.addEventListener('blur', () => editor.classList.remove('is-focused'));

    // Au chargement, focus doux et placeholder
    editor.focus();
    syncPlaceholder();

    form.addEventListener('submit', () => {
        textarea.value = editor.innerHTML;
    });
</script>
    
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
</style>
@endsection
