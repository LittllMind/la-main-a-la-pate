@extends('layouts.public')

@section('title', 'Modifier un sujet — La Main à la Pâte')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-slate-900 mb-6">Modifier le sujet</h1>

    <form method="POST" action="{{ route('subjects.update', $subject->slug) }}" class="bg-white rounded-lg border border-slate-200 p-6">
        @csrf
        @method('PUT')

        <div class="mb-5">
            <label for="theme" class="block text-sm font-medium text-slate-700 mb-1">Thème</label>
            <select id="theme" name="theme" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                @foreach($themes as $theme)
                    <option value="{{ $theme }}" {{ old('theme', $subject->theme) === $theme ? 'selected' : '' }}>{{ $theme }}</option>
                @endforeach
            </select>
            @error('theme')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5">
            <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Titre</label>
            <input id="title" name="title" type="text" value="{{ old('title', $subject->title) }}" maxlength="255" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
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
                <button type="button" data-cmd="createLink" class="toolbar-btn toolbar-link px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Lien</button>
            </div>
            <div id="editor" contenteditable="true" data-placeholder="Cliquez ici pour rédiger le document..." class="editor-contenteditable w-full min-h-[200px] border border-slate-300 rounded-md px-3 py-2 text-sm prose max-w-none">{!! old('body', $subject->body) !!}</div>
            <textarea id="body" name="body" hidden>{{ old('body', $subject->body) }}</textarea>
            @error('body')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5">
            <label for="change_summary" class="block text-sm font-medium text-slate-700 mb-1">Résumé des modifications (optionnel)</label>
            <input id="change_summary" name="change_summary" type="text" maxlength="255" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">Enregistrer</button>
            <a href="{{ route('subjects.show', $subject->slug) }}" class="text-slate-500 text-sm hover:text-slate-800 transition">Annuler</a>
        </div>
    </form>
</div>

<script>
    const editor = document.getElementById('editor');
    const textarea = document.getElementById('body');

    function exec(cmd, arg = null) {
        document.execCommand(cmd, false, arg);
        editor.focus();
        syncPlaceholder();
    }

    function insertLink() {
        const selection = document.getSelection();
        const selectedText = selection.toString().trim();
        const url = prompt('Adresse du lien (https://...)');
        if (!url) return;

        // Si du texte est sélectionné, on le remplace par un lien.
        // Sinon on insère l'URL comme texte du lien à la position du curseur.
        let linkText = selectedText || url;
        const linkHtml = `<a href="${url}" rel="noopener noreferrer" target="_blank" class="text-emerald-700 hover:text-emerald-800 hover:underline">${linkText}</a>`;

        if (selectedText) {
            document.execCommand('insertHTML', false, linkHtml);
        } else {
            document.execCommand('insertHTML', false, linkHtml + '\u0026nbsp;');
        }

        editor.focus();
        syncPlaceholder();
    }

    function updateToolbarState() {
        document.querySelectorAll('.toolbar-btn').forEach(btn => {
            const cmd = btn.dataset.cmd;
            let active = false;
            if (cmd === 'formatBlock') {
                const arg = btn.dataset.arg;
                const current = document.queryCommandValue('formatBlock');
                active = current === arg || current === arg.toUpperCase();
            } else if (cmd === 'insertUnorderedList') {
                active = document.queryCommandState('insertUnorderedList');
            } else if (cmd === 'createLink') {
                active = document.queryCommandValue('createLink') !== 'false';
            } else {
                active = document.queryCommandState(cmd);
            }
            btn.classList.toggle('toolbar-active', active);
        });
    }

    document.querySelectorAll('.toolbar-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const cmd = btn.dataset.cmd;
            const arg = btn.dataset.arg || null;
            if (cmd === 'createLink') {
                insertLink();
            } else {
                exec(cmd, arg);
            }
            updateToolbarState();
        });
    });

    function syncPlaceholder() {
        if (editor.innerText.trim().length === 0) {
            editor.classList.add('is-empty');
        } else {
            editor.classList.remove('is-empty');
        }
    }

    editor.addEventListener('input', () => {
        syncPlaceholder();
        updateToolbarState();
    });
    editor.addEventListener('keyup', updateToolbarState);
    editor.addEventListener('mouseup', updateToolbarState);
    editor.addEventListener('focus', () => editor.classList.add('is-focused'));
    editor.addEventListener('blur', () => editor.classList.remove('is-focused'));

    syncPlaceholder();
    updateToolbarState();

    document.querySelector('form').addEventListener('submit', () => {
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
