@extends('layouts.public')

@section('title', 'Nouveau sujet — La Main à la Pâte')

@section('content')<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-slate-900 mb-6">Nouveau sujet</h1>

    <form method="POST" action="{{ route('subjects.store') }}" class="bg-white rounded-lg border border-slate-200 p-6" id="subject-form">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
            <div>
                <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1">Thème</label>
                <select id="category_id" name="category_id" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm" data-category-select>
                    <option value="">-- Choisir un thème --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', request('category_id')) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
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

        {{-- legacy hidden theme pour compatibilité --}}
        <input type="hidden" name="theme" id="theme_legacy" value="{{ old('theme') }}">

        <div class="mb-5">
            <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Titre</label>
            <input id="title" name="title" type="text" value="{{ old('title') }}" maxlength="255" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
            @error('title')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5" data-markdown-editor data-help-reference>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-slate-700">Contenu du document</label>
                <span class="text-xs text-slate-500">Rédaction au format Markdown — simple et clair</span>
            </div>

            <div class="border border-slate-300 rounded-md p-2 mb-2 flex flex-wrap gap-2 bg-slate-50" aria-label="Mises en formes courantes">
                <button type="button" data-insert="## " data-tip="Titre principal : ## Mon titre" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Titre <span class="text-slate-400">#</span></button>
                <button type="button" data-insert="### " data-tip="Sous-titre : ### Mon sous-titre" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Sous-titre <span class="text-slate-400">#</span></button>
                <button type="button" data-insert="**texte**" data-tip="Texte en gras : **mon mot**" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Gras <span class="text-slate-400">**</span></button>
                <button type="button" data-insert="*texte*" data-tip="Texte en italique : *mon mot*" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Italique <span class="text-slate-400">*</span></button>
                <button type="button" data-insert="\n- élément\n" data-tip="Liste à puces : - élément" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Liste <span class="text-slate-400">-</span></button>
                <button type="button" data-insert="\n> " data-tip="Citation : > une phrase" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Citation <span class="text-slate-400">></span></button>
                <button type="button" data-insert="\n| Colonne 1 | Colonne 2 |\n| --- | --- |\n| a | b |\n" data-tip="Tableau : utilise des | et des -" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Tableau <span class="text-slate-400">|</span></button>
                <button type="button" data-insert="[texte](https://)" data-tip="Lien : [texte](https://...)" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Lien <span class="text-slate-400">[...](...)</span></button>
                <button type="button" data-insert="\n![légende](https://)\n" data-tip="Image : ![légende](https://...)" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Image URL <span class="text-slate-400">![...](...)</span></button>
            </div>

            <textarea id="body" name="body" rows="16" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Exemple de rédaction simple :

# Mon sujet

Un paragraphe introduit le document.

## Une idée importante

- Un premier point
- Un deuxième point

> Une citation issue d'une réunion ou d'un document

Ajoutez des images dans la galerie du sujet, puis cliquez 'Copier markdown' pour les insérer ici.">{{ old('body') }}</textarea>

            <div class="mt-4">
                <div class="text-xs font-medium text-slate-500 flex items-center gap-2 mb-2">
                    <span>Aperçu du rendu</span>
                    <button type="button" id="toggle-preview" class="text-emerald-700">Masquer</button>
                </div>
                <div id="preview" class="prose prose-slate max-w-none border border-slate-200 rounded-md p-4 min-h-[120px]"></div>
            </div>

            @error('body')
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

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">Créer en brouillon</button>
            <a href="{{ route('subjects.index') }}" class="text-slate-500 text-sm hover:text-slate-800 transition">Annuler</a>
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

    @if(old('category_id', request('category_id')))
        populateSubs({{ old('category_id', request('category_id')) }}, {{ old('sub_category_id', request('sub_category_id', 'null')) }});
    @endif
})();
</script>
@endsection
