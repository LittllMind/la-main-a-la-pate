@extends('layouts.public')

@section('title', 'Modifier un sujet — La Main à la Pâte')

@section('content')<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-slate-900 mb-6">Modifier le sujet</h1>

    <div class="flex items-center gap-3 mb-4">
        <a href="{{ route('subjects.documents.index', $subject->slug) }}" class="bg-slate-100 text-slate-700 px-3 py-1.5 rounded text-sm font-medium hover:bg-slate-200 transition">
            📎 Pièces jointes ({{ $subject->documents->count() }})
        </a>
        <a href="{{ route('subjects.pdf.show', $subject->slug) }}" target="_blank" class="bg-slate-100 text-slate-700 px-3 py-1.5 rounded text-sm font-medium hover:bg-slate-200 transition">
            📄 Voir en PDF
        </a>
    </div>

    <form method="POST" action="{{ route('subjects.update', $subject->slug) }}" class="bg-white rounded-lg border border-slate-200 p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
            <div>
                <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1">Thème</label>
                <select id="category_id" name="category_id" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm" data-category-select>
                    <option value="">-- Choisir un thème --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $subject->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
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
        <input type="hidden" name="theme" id="theme_legacy" value="{{ old('theme', $subject->theme) }}">

        <div class="mb-5">
            <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Titre</label>
            <input id="title" name="title" type="text" value="{{ old('title', $subject->title) }}" maxlength="255" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
            @error('title')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5" data-markdown-editor data-help-reference>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-slate-700">Contenu du document</label>
                <span class="text-xs text-slate-500">Rédaction au format Markdown — simple et clair</span>
            </div>

            <div class="border border-slate-300 rounded-md p-2 mb-2 flex flex-wrap gap-2 bg-slate-50" aria-label="Mises en forme courantes">
                <button type="button" data-insert="## " data-tip="Titre principal : ## Mon titre" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Titre <span class="text-slate-400">#</span></button>
                <button type="button" data-insert="### " data-tip="Sous-titre : ### Mon sous-titre" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Sous-titre <span class="text-slate-400">#</span></button>
                <button type="button" data-insert="**texte**" data-tip="Texte en gras : **mon mot**" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Gras <span class="text-slate-400">**</span></button>
                <button type="button" data-insert="*texte*" data-tip="Texte en italique : *mon mot*" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Italique <span class="text-slate-400">*</span></button>
                <button type="button" data-insert="\n- élément\n" data-tip="Liste à puces : - élément" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Liste <span class="text-slate-400">-</span></button>
                <button type="button" data-insert="\n\u003e " data-tip="Citation : \u003e une phrase" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Citation <span class="text-slate-400">\u003e</span></button>
                <button type="button" data-insert="\n| Colonne 1 | Colonne 2 |\n| --- | --- |\n| a | b |\n" data-tip="Tableau : utilise des | et des -" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Tableau <span class="text-slate-400">|</span></button>
                <button type="button" data-insert="[texte](https://)" data-tip="Lien : [texte](https://...)" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Lien <span class="text-slate-400">[...](...)</span></button>
                <button type="button" data-insert="\n![légende](https://)\n" data-tip="Image : ![légende](https://...)" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Image URL <span class="text-slate-400">![...](...)</span></button>
                <a href="{{ route('subjects.images.index', $subject->slug) }}" target="_blank" class="toolbar-btn px-2 py-1 text-xs rounded bg-emerald-50 border border-emerald-300 hover:bg-emerald-100">Galerie du sujet</a>
                <label class="toolbar-btn px-2 py-1 text-xs rounded bg-emerald-50 border border-emerald-300 hover:bg-emerald-100 cursor-pointer">
                    <input type="file" accept="image/*" class="hidden" data-inline-upload data-subject-id="{{ $subject->slug }}">
                    Ajouter une image
                </label>
            </div>

            <textarea id="body" name="body" rows="16" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Rédigez ici au format Markdown. Utilisez les boutons ci-dessus pour découvrir la syntaxe.">{{ old('body', $subject->body) }}</textarea>

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
                    <div><span class="font-mono bg-slate-100 px-1 rounded">\u003e citation</span> → Citation</div>
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
            <input id="change_summary" name="change_summary" type="text" maxlength="255" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">Enregistrer</button>
            <a href="{{ route('subjects.show', $subject->slug) }}" class="text-slate-500 text-sm hover:text-slate-800 transition">Annuler</a>
        </div>
    </form>

    {{-- Section Collaboration — hors du form principal pour éviter l'imbrication --}}
    <div class="bg-white rounded-lg border border-slate-200 p-6 mt-6">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Collaborateurs</h2>

        @php
            $isAuthorOrAdmin = auth()->user()->isAdmin() || auth()->user()->id === $subject->user_id;
        @endphp

        {{-- Liste des collaborateurs existants --}}
        @if($subject->collaborators->isNotEmpty())
            <ul class="space-y-1 mb-3">
                @foreach($subject->collaborators as $collaborator)
                    <li class="flex items-center justify-between text-sm">
                        <span class="text-slate-700">{{ $collaborator->name }} ({{ $collaborator->email }})</span>
                        @if($isAuthorOrAdmin)
                            <form method="POST" action="{{ route('subjects.collaborators.destroy', [$subject->slug, $collaborator]) }}" class="inline" onsubmit="return confirm('Retirer ce collaborateur ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 text-xs hover:underline">Retirer</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{-- État du vote --}}
            @php
                $votes = $subject->publicationVotes->keyBy('user_id');
            @endphp
            <div class="mb-3 text-sm">
                <span class="font-medium text-slate-700">État du vote de publication :</span>
                @foreach($subject->collaborators as $collaborator)
                    @php $vote = $votes[$collaborator->id] ?? null; @endphp
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-slate-600">{{ $collaborator->name }} :</span>
                        @if($vote && $vote->vote === 'approved')
                            <span class="text-emerald-600 font-medium">Approuvé</span>
                        @elseif($vote && $vote->vote === 'rejected')
                            <span class="text-red-600 font-medium">Rejeté</span>
                        @else
                            <span class="text-amber-600">En attente</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-slate-500 text-sm mb-3">Aucun collaborateur sur ce sujet.</p>
        @endif

        {{-- Ajouter un collaborateur --}}
        @if($isAuthorOrAdmin)
            <form method="POST" action="{{ route('subjects.collaborators.store', $subject->slug) }}" class="flex items-end gap-2 mb-3">
                @csrf
                <div class="flex-1">
                    <label class="block text-xs text-slate-500 mb-1">Ajouter un citoyen</label>
                    <select name="user_id" class="w-full border border-slate-300 rounded-md px-2 py-1.5 text-sm">
                        <option value="">-- Sélectionner --</option>
                        @foreach(App\Models\User::whereIn('role', ['citoyen', 'moderator', 'admin'])->orderBy('name')->get() as $citizen)
                            @if($citizen->id !== $subject->user_id && !$subject->collaborators->contains('id', $citizen->id))
                                <option value="{{ $citizen->id }}">{{ $citizen->name }} ({{ $citizen->email }})</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-slate-700 text-white px-3 py-1.5 rounded-md text-sm hover:bg-slate-800">Ajouter</button>
            </form>

            {{-- Démarrer un vote --}}
            @if($subject->collaborators->isNotEmpty() && $subject->status === 'draft')
                <form method="POST" action="{{ route('subjects.collaborators.startVote', $subject->slug) }}" class="mb-2">
                    @csrf
                    <button type="submit" class="text-sm text-emerald-700 font-medium hover:underline">
                        🗳️ Lancer le vote de publication
                    </button>
                </form>
            @endif
        @endif

        {{-- Voter (visible par les collaborateurs) --}}
        @if($subject->isCollaborator(auth()->user()) && $subject->status === 'draft')
            @php
                $myVote = $subject->publicationVotes()->where('user_id', auth()->id())->first();
            @endphp
            @if($myVote && $myVote->vote === 'pending')
                <div class="flex gap-2 mt-2">
                    <form method="POST" action="{{ route('subjects.collaborators.vote', $subject->slug) }}">
                        @csrf
                        <input type="hidden" name="vote" value="approved">
                        <button type="submit" class="bg-emerald-600 text-white px-3 py-1 rounded text-sm hover:bg-emerald-700">✓ Approuver</button>
                    </form>
                    <form method="POST" action="{{ route('subjects.collaborators.vote', $subject->slug) }}">
                        @csrf
                        <input type="hidden" name="vote" value="rejected">
                        <button type="submit" class="bg-red-100 text-red-700 border border-red-300 px-3 py-1 rounded text-sm hover:bg-red-200">✗ Rejeter</button>
                    </form>
                </div>
            @elseif($myVote)
                <p class="text-sm text-slate-500 mt-2">Vous avez voté : <strong>{{ $myVote->vote === 'approved' ? 'Approuvé' : 'Rejeté' }}</strong></p>
            @endif
        @endif
    </div>
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

    // Init avec valeur existante du sujet
    @if($subject->category_id)
        populateSubs({{ $subject->category_id }}, {{ $subject->sub_category_id ?? 'null' }});
    @endif
})();
</script>
@endsection
