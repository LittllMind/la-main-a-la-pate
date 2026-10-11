@extends('layouts.public')

@section('title', 'Proposer un sujet — La Main à la Pâte')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-slate-900 mb-6">Proposer un sujet</h1>

    <form method="POST" action="{{ route('subjects.store') }}" id="subject-form" class="bg-white rounded-lg border border-slate-200 p-6">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
            <div>
                <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1">Thème</label>
                <select id="category_id" name="category_id" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm" data-category-select>
                    <option value="">-- Choisir un thème --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="sub_category_id" class="block text-sm font-medium text-slate-700 mb-1">Sous-thème</label>
                <select id="sub_category_id" name="sub_category_id" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                    <option value="">-- Choisir d'abord un thème --</option>
                </select>
                @error('sub_category_id')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <input type="hidden" name="theme" id="theme_legacy" value="{{ old('theme') }}">

        <div class="mb-5">
            <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Titre</label>
            <input id="title" name="title" type="text" value="{{ old('title') }}" maxlength="255" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
            @error('title')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5" data-markdown-editor data-help-reference>
            <div class="flex items-center border-b border-slate-200 mb-3" role="tablist">
                <button type="button" data-tab="body" data-editor-tab="body" aria-controls="body-panel" class="version-tab px-4 py-2 text-sm font-medium text-slate-700 border-b-2 border-emerald-600" aria-selected="true" role="tab">
                    Instruction
                    <span class="ml-2 px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-600 border border-slate-200">interne</span>
                </button>
                @if(auth()->user()->isAdmin())
                <button type="button" data-tab="citizen_body" data-editor-tab="citizen_body" aria-controls="citizen_body-panel" class="version-tab px-4 py-2 text-sm font-medium text-slate-500 border-b-2 border-transparent hover:text-slate-700" role="tab">
                    Public connecté
                    <span class="ml-2 px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-600 border border-slate-200">optionnel</span>
                </button>
                <button type="button" data-tab="public_body" data-editor-tab="public_body" aria-controls="public_body-panel" class="version-tab px-4 py-2 text-sm font-medium text-slate-500 border-b-2 border-transparent hover:text-slate-700" role="tab">
                    Public déconnecté
                    <span class="ml-2 px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-600 border border-slate-200">optionnel</span>
                </button>
                @endif
            </div>

            <div id="body-panel" data-tab-panel="body" data-editor-panel="body" class="tab-panel active" role="tabpanel" aria-labelledby="body-tab">
                @include('subjects._editor_partial', [
                    'textareaId' => 'body',
                    'textareaName' => 'body',
                    'audienceLabel' => 'Instruction',
                    'placeholder' => 'Rédigez ici au format Markdown.',
                    'content' => old('body', ''),
                    'subject' => $subject ?? null,
                    'hideInlineUpload' => true,
                ])
            </div>

            @if(auth()->user()->isAdmin())
            <div id="citizen_body-panel" data-tab-panel="citizen_body" data-editor-panel="citizen_body" class="tab-panel hidden" role="tabpanel" aria-labelledby="citizen_body-tab">
                <div class="flex items-center gap-2 mb-2 text-xs text-slate-500">
                    <span class="italic">Visible par les membres identifiés.</span>
                </div>
                @include('subjects._editor_partial', [
                    'textareaId' => 'citizen_body',
                    'textareaName' => 'citizen_body',
                    'audienceLabel' => 'Public connecté',
                    'placeholder' => 'Version destinée aux membres identifiés.',
                    'content' => old('citizen_body', ''),
                    'subject' => $subject ?? null,
                    'hideInlineUpload' => true,
                ])
            </div>
            <div id="public_body-panel" data-tab-panel="public_body" data-editor-panel="public_body" class="tab-panel hidden" role="tabpanel" aria-labelledby="public_body-tab">
                <div class="flex items-center gap-2 mb-2 text-xs text-slate-500">
                    <span class="italic">Visible publiquement.</span>
                </div>
                @include('subjects._editor_partial', [
                    'textareaId' => 'public_body',
                    'textareaName' => 'public_body',
                    'audienceLabel' => 'Public déconnecté',
                    'placeholder' => 'Version publique : synthèse officielle, faits établis uniquement.',
                    'content' => old('public_body', ''),
                    'subject' => $subject ?? null,
                    'hideInlineUpload' => true,
                ])
            </div>
            @endif

            <div class="mt-4">
                <div class="text-xs font-medium text-slate-500 flex flex-wrap items-center gap-2 mb-2">
                    <span>Aperçu du rendu</span>
                    <strong data-preview-audience>Instruction</strong>
                    <button type="button" data-preview-mode="write-preview" aria-selected="true" class="text-emerald-700">Écrire + aperçu</button>
                    <button type="button" data-preview-mode="write" aria-selected="false" class="text-emerald-700">Écrire</button>
                    <button type="button" data-preview-mode="preview" aria-selected="false" class="text-emerald-700">Aperçu</button>
                </div>
                <div id="preview" data-markdown-preview data-preview-panel aria-label="Aperçu du rendu Markdown" class="subject-preview prose prose-slate max-w-none border border-slate-200 rounded-md p-4 max-h-96 overflow-y-auto"></div>
            </div>

            @error('body')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
            @error('citizen_body')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
            @error('public_body')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror

            <details class="mt-4 text-sm text-slate-600 border border-slate-200 rounded-md">
                <summary class="cursor-pointer px-3 py-2 bg-slate-50 font-medium">Rappel des raccourcis Markdown</summary>
                <div class="p-3 space-y-1 text-xs grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div><span class="font-mono bg-slate-100 px-1 rounded"># Titre</span> → Titre principal</div>
                    <div><span class="font-mono bg-slate-100 px-1 rounded">## Titre</span> → Sous-titre</div>
                    <div><span class="font-mono bg-slate-100 px-1 rounded">**mot**</span> → Gras</div>
                    <div><span class="font-mono bg-slate-100 px-1 rounded">*mot*</span> → Italique</div>
                    <div><span class="font-mono bg-slate-100 px-1 rounded">- élément</span> → Liste à puces</div>
                    <div><span class="font-mono bg-slate-100 px-1 rounded">> citation</span> → Citation</div>
                    <div><span class="font-mono bg-slate-100 px-1 rounded">[texte](https://...)</span> → Lien</div>
                    <div><span class="font-mono bg-slate-100 px-1 rounded">![légende](...)</span> → Image</div>
                    <div class="sm:col-span-2 text-slate-500">
                        Astuce : utilisez des lignes vides pour séparer les paragraphes. Les tableaux se construisent avec des | et une ligne |---|---|.
                    </div>
                </div>
            </details>
        </div>

        <div class="mb-5">
            <label for="change_summary" class="block text-sm font-medium text-slate-700 mb-1">Résumé des modifications (optionnel)</label>
            <input id="change_summary" name="change_summary" type="text" maxlength="255" value="{{ old('change_summary') }}" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
        </div>

        <div class="sticky bottom-0 -mx-6 -mb-6 px-6 py-3 bg-white/95 backdrop-blur border-t border-slate-200 flex items-center gap-3 z-40">
            <span data-unsaved-indicator class="hidden text-amber-700 text-sm font-medium" aria-hidden="true">Modifications non enregistrées</span>
            <button type="submit" data-save-button class="bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">Enregistrer</button>
            <a href="{{ url()->previous() }}" data-cancel-link data-confirm-leave class="text-slate-500 text-sm hover:text-slate-800 transition">Annuler</a>
        </div>
    </form>
</div>

<script>
(function(){
    const categories = {!! $categoriesJson !!};
    const catSelect = document.getElementById('category_id');
    const subSelect = document.getElementById('sub_category_id');
    const themeInput = document.getElementById('theme_legacy');

    function populateSubs(catId, selectedSubId) {
        subSelect.innerHTML = '<option value="">-- Choisir un sous-thème --</option>';
        if (!catId) return;
        const cat = categories.find(c => c.id == catId);
        if (!cat) return;
        cat.subs.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name;
            if (selectedSubId && s.id == selectedSubId) opt.selected = true;
            subSelect.appendChild(opt);
        });
        themeInput.value = cat.name;
    }

    catSelect.addEventListener('change', function() {
        populateSubs(this.value, null);
    });

    @if(old('category_id'))
        populateSubs({{ old('category_id') }}, {{ old('sub_category_id') ?? 'null' }});
    @endif
})();
</script>
@endsection
