@extends('layouts.public')

@section('title', 'Modifier la fiche — ' . $subject->title . ' — La Main à la Pâte')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <a href="{{ route('subjects.documents.index', $subject->slug) }}" class="text-sm text-slate-500 hover:text-slate-900 mb-4 inline-block">← Retour aux documents</a>

    <div class="mb-6">
        <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs">{{ $subject->theme }}</span>
        <h1 class="text-2xl font-bold text-slate-900 mt-2">Modifier la fiche documentaire</h1>
        <p class="text-slate-500 text-sm">{{ $document->title ?: $document->filename }}</p>
    </div>

    @if($document->isSecure())
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 mb-4 text-sm text-emerald-800">
            Le fichier reste inchangé — vous modifiez uniquement les métadonnées.
        </div>
    @endif

    <form method="POST" action="{{ route('subjects.documents.update', [$subject->slug, $document->id]) }}" class="bg-white rounded-lg border border-slate-200 p-5 space-y-6">
        @csrf
        @method('PATCH')

        {{-- Identification --}}
        <div>
            <h2 class="text-sm font-semibold text-slate-800 mb-3 pb-1 border-b border-slate-100">Identification</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs text-slate-500 mb-1">Titre</label>
                    <input type="text" name="title" value="{{ old('title', $document->title) }}" maxlength="255" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                </div>

                <div>
                    <label class="block text-xs text-slate-500 mb-1">Date du document</label>
                    <input type="date" name="document_date" value="{{ old('document_date', $document->document_date?->format('Y-m-d')) }}" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                </div>

                <div>
                    <label class="block text-xs text-slate-500 mb-1">Nature du document</label>
                    <input type="text" name="document_type" value="{{ old('document_type', $document->document_type) }}" list="document-type-suggestions" maxlength="80" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                    <datalist id="document-type-suggestions">
                        <option value="convention"></option>
                        <option value="sommation"></option>
                        <option value="mail"></option>
                        <option value="délibération"></option>
                        <option value="AOT"></option>
                        <option value="profession de foi"></option>
                        <option value="CR Conseil municipal"></option>
                    </datalist>
                </div>

                <div>
                    <label class="block text-xs text-slate-500 mb-1">Auteur / organisme</label>
                    <input type="text" name="author" value="{{ old('author', $document->author) }}" maxlength="255" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                </div>

                <div>
                    <label class="block text-xs text-slate-500 mb-1">Destinataire</label>
                    <input type="text" name="recipient" value="{{ old('recipient', $document->recipient) }}" maxlength="255" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                </div>
            </div>
        </div>

        {{-- Traçabilité interne --}}
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <h2 class="text-sm font-semibold text-amber-900 mb-2 flex items-center gap-2">
                <span>🔒</span>
                <span>Traçabilité interne</span>
            </h2>
            <p class="text-xs text-amber-800 mb-2">Ce champ n'est jamais affiché aux lecteurs publics ou citoyens.</p>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Origine / source de la copie</label>
                <input type="text" name="source_reference" value="{{ old('source_reference', $document->source_reference) }}" maxlength="2000" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
            </div>
        </div>

        {{-- Diffusion --}}
        <div>
            <h2 class="text-sm font-semibold text-slate-800 mb-3 pb-1 border-b border-slate-100">Diffusion</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Visibilité</label>
                    <select name="visibility" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                        @foreach(\App\Models\VisibilityLevel::cases() as $level)
                            <option value="{{ $level->value }}" {{ old('visibility', $document->visibility?->value) === $level->value ? 'selected' : '' }}>
                                {{ $level->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-slate-500 mb-1">Type de représentation</label>
                    <select name="representation_type" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                        <option value="" {{ old('representation_type', $document->representation_type?->value) === null ? 'selected' : '' }}>--</option>
                        @foreach(\App\Models\RepresentationType::cases() as $type)
                            <option value="{{ $type->value }}" {{ old('representation_type', $document->representation_type?->value) === $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <div class="flex items-center gap-2 h-10">
                        <input type="checkbox" name="redacted" id="redacted" value="1" {{ old('redacted', $document->redacted) ? 'checked' : '' }} class="rounded border-slate-300">
                        <label for="redacted" class="text-sm text-slate-700">Document expurgé (cette copie)</label>
                    </div>
                </div>
            </div>
        <div>
            <h2 class="text-sm font-semibold text-slate-800 mb-3 pb-1 border-b border-slate-100">Classification</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Catégorie</label>
                    <select name="category" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                        @foreach(['source' => 'Source originale', 'ocr' => 'OCR / transcription', 'annexe' => 'Annexe', 'audio' => 'Audio', 'autre' => 'Autre'] as $value => $label)
                            <option value="{{ $value }}" {{ old('category', $document->category) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-slate-500 mb-1">Position</label>
                    <input type="number" name="position" value="{{ old('position', $document->position) }}" min="0" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                </div>
            </div>
        </div>

        {{-- Contextualisation --}}
        <div>
            <h2 class="text-sm font-semibold text-slate-800 mb-3 pb-1 border-b border-slate-100">Contextualisation</h2>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Description / intérêt documentaire</label>
                    <textarea name="description" rows="2" maxlength="1000" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">{{ old('description', $document->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs text-slate-500 mb-1">Ce qu’il établit</label>
                    <textarea name="establishes" rows="3" maxlength="5000" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">{{ old('establishes', $document->establishes) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs text-slate-500 mb-1">Ce qu’il n’établit pas / précautions</label>
                    <textarea name="limitations" rows="3" maxlength="5000" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">{{ old('limitations', $document->limitations) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="bg-emerald-700 text-white px-4 py-2 rounded text-sm hover:bg-emerald-800">Enregistrer la fiche</button>
            <a href="{{ route('subjects.documents.index', $subject->slug) }}" class="text-sm text-slate-500 hover:text-slate-900">Annuler</a>
        </div>
    </form>
</div>
@endsection
