@extends('layouts.public')

@section('title', 'Documents — ' . $subject->title . ' — La Main à la Pâte')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <a href="{{ route('subjects.show', $subject->slug) }}" class="text-sm text-slate-500 hover:text-slate-900 mb-4 inline-block">← Retour au sujet</a>

    <div class="mb-6">
        <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs">{{ $subject->theme }}</span>
        <h1 class="text-2xl font-bold text-slate-900 mt-2">Documents du sujet</h1>
        <p class="text-slate-500 text-sm">{{ $subject->title }}</p>
    </div>

    {{-- Upload form --}}
    @can('update', $subject)
    <div class="bg-white rounded-lg border border-slate-200 p-4 mb-6">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">📎 Ajouter un document (fichier)</h2>
        <form method="POST" action="{{ route('subjects.documents.store', $subject->slug) }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            
            <div class="flex items-center gap-3">
                <input type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.mp3,.wav,.ogg,.zip,.md" required
                       class="text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-emerald-700 file:text-white hover:file:bg-emerald-800">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Titre</label>
                    <input type="text" name="title" placeholder="Ex: Acte de deces" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Categorie</label>
                    <select name="category" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                        <option value="source">📄 Source originale</option>
                        <option value="ocr">📝 OCR / transcription</option>
                        <option value="annexe">📎 Annexe</option>
                        <option value="audio">🔊 Audio</option>
                        <option value="autre">❓ Autre</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Visibilité</label>
                    <select name="visibility" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                        @foreach(\App\Models\VisibilityLevel::cases() as $level)
                            <option value="{{ $level->value }}" {{ $level->value === 'working' ? 'selected' : '' }}>
                                {{ $level->label() }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 mt-0.5">Par défaut : interne uniquement.</p>
                </div>
            </div>

            <div>
                <label class="block text-xs text-slate-500 mb-1">Description (optionnel)</label>
                <textarea name="description" rows="2" placeholder="Contexte du document..." class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm"></textarea>
            </div>

            <button type="submit" class="bg-emerald-700 text-white px-4 py-1.5 rounded text-sm hover:bg-emerald-800">📎 Attacher le document</button>

            @can('update', $subject)
            <details class="mt-3 border border-slate-200 rounded-md">
                <summary class="px-3 py-2 bg-slate-50 text-xs font-medium cursor-pointer">Métadonnées documentaires</summary>
                <div class="p-3 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[10px] text-slate-500 mb-1">Date du document</label>
                            <input type="date" name="document_date" class="w-full border border-slate-300 rounded px-2 py-1 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-500 mb-1">Nature</label>
                            <input type="text" name="document_type" list="document-type-suggestions" placeholder="Ex: sommation" maxlength="80" class="w-full border border-slate-300 rounded px-2 py-1 text-sm">
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
                            <label class="block text-[10px] text-slate-500 mb-1">Auteur / organisme</label>
                            <input type="text" name="author" placeholder="Ex: Commune du Rozier" maxlength="255" class="w-full border border-slate-300 rounded px-2 py-1 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-500 mb-1">Destinataire</label>
                            <input type="text" name="recipient" placeholder="Ex: La Séraphothèque" maxlength="255" class="w-full border border-slate-300 rounded px-2 py-1 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] text-slate-500 mb-1">Origine / source de la copie (interne)</label>
                            <input type="text" name="source_reference" placeholder="Archive LEX / chemin interne" maxlength="2000" class="w-full border border-slate-300 rounded px-2 py-1 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-500 mb-1">Type de représentation</label>
                            <select name="representation_type" class="w-full border border-slate-300 rounded px-2 py-1 text-sm">
                                <option value="">--</option>
                                @foreach(\App\Models\RepresentationType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="redacted" id="redacted" value="1" class="rounded border-slate-300">
                        <label for="redacted" class="text-xs text-slate-700">Document expurgé (cette copie)</label>
                    </div>

                    <div>
                        <label class="block text-[10px] text-slate-500 mb-1">Ce qu'il établit</label>
                        <textarea name="establishes" rows="2" maxlength="5000" placeholder="Faits strictement démontrés par ce document." class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] text-slate-500 mb-1">Ce qu'il n'établit pas</label>
                        <textarea name="limitations" rows="2" maxlength="5000" placeholder="Limites et précautions d'interprétation." class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></textarea>
                    </div>
                </div>
            </details>
            @endcan
        </form>
    </div>

    <!-- Markdown to PDF generator -->
    <div class="bg-white rounded-lg border border-slate-200 p-4 mb-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-800">📝 Generer un PDF depuis du texte Markdown</h2>
            <span class="text-xs text-slate-500">Le contenu est converti en PDF et attache comme document</span>
        </div>
        <form method="POST" action="{{ route('subjects.documents.markdown-pdf', $subject->slug) }}" class="space-y-3">
            @csrf
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Titre du document</label>
                    <input type="text" name="title" placeholder="Ex: Courrier collectif au maire" required maxlength="255" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Categorie</label>
                    <select name="category" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
                        <option value="annexe">📎 Annexe</option>
                        <option value="source">📄 Source originale</option>
                        <option value="ocr">📝 OCR / transcription</option>
                        <option value="autre">❓ Autre</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs text-slate-500 mb-1">Contenu Markdown</label>
                <textarea name="markdown" rows="12" placeholder="# Titre

Redigez ici en Markdown. Les tableaux, listes, citations et liens seront preserves dans le PDF." required maxlength="50000" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm font-mono"></textarea>
            </div>

            <div>
                <label class="block text-xs text-slate-500 mb-1">Description (optionnel)</label>
                <input type="text" name="description" placeholder="Contexte..." maxlength="1000" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
            </div>

            <button type="submit" class="bg-emerald-700 text-white px-4 py-1.5 rounded text-sm hover:bg-emerald-800">📄 Generer et attacher le PDF</button>
        </form>
    </div>
    @endcan

    {{-- Liste des documents --}}
    @if($subject->documents->count() > 0)
        <div class="space-y-3">
            @foreach($subject->documents as $doc)
            <div class="bg-white rounded-lg border border-slate-200 p-4 flex items-start gap-4">
                <div class="text-2xl flex-shrink-0">{{ $doc->icon() }}</div>
                
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="text-sm font-medium text-slate-900 truncate">{{ $doc->title }}</span>
                        <span class="text-xs px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">{{ $doc->extension() }}</span>
                        <span class="text-xs text-slate-400">{{ $doc->humanSize() }}</span>
                        @if($doc->visibility)
                            <span class="text-xs px-1.5 py-0.5 rounded bg-{{ $doc->visibility->color() }}-100 text-{{ $doc->visibility->color() }}-700 border border-{{ $doc->visibility->color() }}-200">
                                {{ $doc->visibility->label() }}
                            </span>
                        @endif
                        @if($doc->representation_type)
                            <span class="text-xs px-1.5 py-0.5 rounded bg-slate-100 text-slate-600" title="Type de représentation">{{ $doc->representation_type->label() }}</span>
                        @endif
                        @if($doc->redacted)
                            <span class="text-xs px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 border border-amber-200">Version expurgée</span>
                        @endif
                        @if($doc->isSecure())
                            <span class="text-xs px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700" title="Stocké de manière privée et chiffrée">🔒 Sécurisé</span>
                        @endif
                    </div>
                    @if($doc->document_date || $doc->document_type || $doc->author || $doc->recipient)
                        <div class="text-xs text-slate-500 mb-1 flex flex-wrap gap-x-3 gap-y-1">
                            @if($doc->document_date)
                                <span>Date : {{ $doc->document_date->format('d/m/Y') }}</span>
                            @endif
                            @if($doc->document_type)
                                <span>Nature : {{ $doc->document_type }}</span>
                            @endif
                            @if($doc->author)
                                <span>Auteur : {{ $doc->author }}</span>
                            @endif
                            @if($doc->recipient)
                                <span>Destinataire : {{ $doc->recipient }}</span>
                            @endif
                        </div>
                    @endif
                    @if($doc->description)
                        <p class="text-xs text-slate-500 mb-1">{{ $doc->description }}</p>
                    @endif
                    @if($doc->establishes || $doc->limitations)
                        <div class="text-xs text-slate-600 mb-1 space-y-1">
                            @if($doc->establishes)
                                <p><span class="font-medium">Établit :</span> {{ $doc->establishes }}</p>
                            @endif
                            @if($doc->limitations)
                                <p><span class="font-medium">N'établit pas :</span> {{ $doc->limitations }}</p>
                            @endif
                        </div>
                    @endif
                    <div class="flex items-center gap-3 text-xs">
                        <span class="text-slate-400">Ajouté le {{ $doc->created_at->format('d/m/Y') }}</span>
                        <span class="text-slate-300">|</span>
                        <a href="{{ route('subjects.documents.download', [$subject->slug, $doc->id]) }}" class="text-emerald-700 hover:underline font-medium">⬇ Télécharger</a>
                        
                        @can('update', $subject)
                            <form method="POST" action="{{ route('subjects.documents.update', [$subject->slug, $doc->id]) }}" class="inline mt-2">
                                @csrf
                                @method('PATCH')
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <select name="visibility" class="border border-slate-300 rounded px-2 py-1 text-sm">
                                        @foreach(\App\Models\VisibilityLevel::cases() as $level)
                                            <option value="{{ $level->value }}" {{ $doc->visibility?->value === $level->value ? 'selected' : '' }}>
                                                {{ $level->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="text-emerald-700 hover:underline">Mettre à jour la visibilité</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('subjects.documents.destroy', [$subject->slug, $doc->id]) }}" class="inline" onsubmit="return confirm('Supprimer ce document ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-xs">Supprimer</button>
                            </form>
                            <a href="{{ route('subjects.documents.edit', [$subject->slug, $doc->id]) }}" class="text-blue-700 hover:underline text-xs">Modifier la fiche</a>
                        @endcan
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="bg-slate-50 rounded-lg border border-slate-200 p-6 text-center">
            <p class="text-slate-500 text-sm">Aucun document attaché à ce sujet.</p>
            @can('update', $subject)
                <p class="text-slate-400 text-xs mt-1">Utilisez le formulaire ci-dessus pour ajouter des sources, transcriptions OCR ou annexes.</p>
            @endcan
        </div>
    @endif
</div>
@endsection
