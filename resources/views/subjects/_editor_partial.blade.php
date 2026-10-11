@php
    $audienceLabel = $audienceLabel ?? 'Instruction';
    $textareaId = $textareaId ?? 'body';
    $textareaName = $textareaName ?? 'body';
    $content = $content ?? '';
    $placeholder = $placeholder ?? 'Rédigez ici au format Markdown.';
@endphp

<div class="subject-editor-wrapper">
    <div class="subject-editor-toolbar sticky top-14 z-40 border border-slate-300 rounded-md p-2 mb-2 flex flex-wrap gap-2 bg-slate-50" aria-label="Barre d'outils Markdown ({{ $audienceLabel }})" role="toolbar">
        <button type="button" data-insert="## " data-tip="Titre de section : ## Mon titre" aria-label="Titre de section" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Titre de section <span class="text-slate-400">##</span></button>
        <button type="button" data-insert="### " data-tip="Sous-section : ### Mon sous-titre" aria-label="Sous-section" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Sous-section <span class="text-slate-400">###</span></button>
        <button type="button" data-insert="**texte**" data-command="bold" data-tip="Texte en gras : **mon mot**" aria-label="Gras" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Gras <span class="text-slate-400">**</span></button>
        <button type="button" data-insert="*texte*" data-command="italic" data-tip="Texte en italique : *mon mot*" aria-label="Italique" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Italique <span class="text-slate-400">*</span></button>
        <button type="button" data-insert="\n- élément\n" data-command="bullet" data-tip="Liste à puces : - élément" aria-label="Liste à puces" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Liste à puces <span class="text-slate-400">-</span></button>
        <button type="button" data-insert="\n1. élément\n" data-tip="Liste numérotée : 1. élément" aria-label="Liste numérotée" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Liste numérotée <span class="text-slate-400">1.</span></button>
        <button type="button" data-insert="\n> " data-tip="Citation : > une phrase" aria-label="Citation" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Citation <span class="text-slate-400">&gt;</span></button>
        <button type="button" data-insert="\n| Colonne 1 | Colonne 2 |\n| --- | --- |\n| a | b |\n" data-tip="Tableau : utilise des | et des -" aria-label="Tableau" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Tableau <span class="text-slate-400">|</span></button>
        <button type="button" data-insert="[texte](https://)" data-tip="Lien : [texte](https://...)" aria-label="Lien" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Lien <span class="text-slate-400">[...](...)</span></button>
        <button type="button" data-insert="\n![légende](https://)\n" data-tip="Image : ![légende](https://...)" aria-label="Image" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Image <span class="text-slate-400">![...](...)</span></button>
        <button type="button" data-insert="\n\`\`\`\nyour code\n\`\`\`\n" data-tip="Bloc de code" aria-label="Bloc de code" class="toolbar-btn px-2 py-1 text-xs rounded bg-white border border-slate-300 hover:bg-slate-100">Code <span class="text-slate-400">```</span></button>
        <a href="{{ isset($subject) ? route('subjects.images.index', $subject->slug) : '#' }}" target="_blank" class="toolbar-btn px-2 py-1 text-xs rounded bg-emerald-50 border border-emerald-300 hover:bg-emerald-100">Galerie du sujet</a>
        @if(!($hideInlineUpload ?? false))
        <label class="toolbar-btn px-2 py-1 text-xs rounded bg-emerald-50 border border-emerald-300 hover:bg-emerald-100 cursor-pointer">
            <input type="file" accept="image/*" class="hidden" data-inline-upload data-subject-id="{{ isset($subject) ? $subject->slug : '' }}">
            Ajouter une image
        </label>
        @endif
    </div>

    <textarea id="{{ $textareaId }}" name="{{ $textareaName }}" data-editor-field data-audience-label="{{ $audienceLabel }}" rows="16" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" placeholder="{{ $placeholder }}">{{ $content }}</textarea>
</div>
