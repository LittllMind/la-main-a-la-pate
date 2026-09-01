<article class="bg-white rounded-lg border border-slate-200 p-6 mb-8 subject-document">
    <div class="prose prose-slate max-w-none subject-markdown">{!! $subject->renderBody() !!}</div>
</article>

@if($subject->images->count() > 0)
    <section class="mb-8">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold text-slate-900">Galerie</h2>
            @can('update', $subject)
                @if(!($isPreview ?? false))
                    <a href="{{ route('subjects.images.index', $subject->slug) }}" class="text-sm text-emerald-700 hover:text-emerald-900">Gérer les images</a>
                @endif
            @endcan
        </div>
        <div class="relative group" data-carousel>
            <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory scroll-smooth pb-3" data-carousel-track>
                @foreach($subject->images->sortBy('position') as $image)
                    <figure class="snap-start shrink-0 w-[85vw] sm:w-[60vw] md:w-[45vw] lg:w-[35vw] rounded-lg border border-slate-200 overflow-hidden bg-white">
                        <a href="{{ $image->url() }}" target="_blank" rel="noopener noreferrer" class="block">
                            <img src="{{ $image->url() }}" alt="{{ $image->alt }}" loading="lazy" class="w-full h-56 sm:h-64 object-cover block">
                        </a>
                        @if($image->alt)
                            <figcaption class="text-xs text-slate-500 p-3 truncate">{{ $image->alt }}</figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
            @if($subject->images->count() > 1)
                <button type="button" data-carousel-prev class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 border border-slate-200 shadow-sm text-slate-700 hover:bg-white hidden sm:flex items-center justify-center opacity-0 group-hover:opacity-100 transition" aria-label="Image précédente">
                    ‹
                </button>
                <button type="button" data-carousel-next class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 border border-slate-200 shadow-sm text-slate-700 hover:bg-white hidden sm:flex items-center justify-center opacity-0 group-hover:opacity-100 transition" aria-label="Image suivante">
                    ›
                </button>
            @endif
        </div>
        @if($subject->images->count() > 1)
            <p class="text-xs text-slate-400 mt-1 sm:hidden">Glisser pour parcourir les {{ $subject->images->count() }} images</p>
        @endif
    </section>
@endif

    {{-- Section Documents Sources --}}
@php
    $currentUser = auth()->user();
    $visibleDocs = $subject->documents->filter(fn($doc) => $doc->visibleTo($currentUser));
    $groupLabels = [
        'primary' => 'PIÈCES PRINCIPALES',
        'dossier' => 'DOSSIERS DOCUMENTAIRES',
        'comparatif' => 'COMPARATIFS DOCUMENTAIRES',
        'context' => 'DOCUMENTS DE CONTEXTE',
        'press' => 'SOURCES DE PRESSE',
        'synthesis' => 'SYNTHÈSES DOCUMENTAIRES',
        'other' => 'AUTRES DOCUMENTS',
    ];
    $groupOrder = ['primary', 'dossier', 'comparatif', 'context', 'press', 'synthesis', 'other'];
    $docsByGroup = $visibleDocs->sortBy(fn($doc) => $doc->seraphothequeOrder())->groupBy(fn($doc) => $doc->seraphothequeGroup());
@endphp
@if($visibleDocs->count() > 0)
    <section id="documents" class="bg-white rounded-lg border border-slate-200 p-6 mb-8">
        <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M6 14h12M3 6h18m-1 14l-4-4h-6l-4 4m-2 0h6"/></svg>
            Sources et documents
        </h2>

        @foreach($groupOrder as $group)
            @php $groupDocs = $docsByGroup->get($group) ?? collect(); @endphp
            @if($groupDocs->count() > 0)
                <div class="mb-6 last:mb-0">
                    <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-3 border-b border-slate-200 pb-1">{{ $groupLabels[$group] }}</h3>
                    <ul class="space-y-3">
                        @foreach($groupDocs as $doc)
                            @php
                                $docTypeLower = strtolower((string) $doc->document_type);
                                $docTypeBadge = match (true) {
                                    str_contains($docTypeLower, 'dossier documentaire') => ['label' => 'DOSSIER DOCUMENTAIRE', 'color' => 'blue'],
                                    str_contains($docTypeLower, 'source de presse') => ['label' => 'SOURCE DE PRESSE', 'color' => 'orange'],
                                    str_contains($docTypeLower, 'source primaire') => ['label' => 'SOURCE PRIMAIRE', 'color' => 'emerald'],
                                    str_contains($docTypeLower, 'synthèse') => ['label' => 'SYNTHÈSE LMALP', 'color' => 'purple'],
                                    str_contains($docTypeLower, 'contexte') => ['label' => 'DOCUMENT DE CONTEXTE', 'color' => 'slate'],
                                    default => null,
                                };
                                if (! $docTypeBadge) {
                                    $docTypeBadge = match ($group) {
                                        'primary' => ['label' => 'SOURCE PRIMAIRE', 'color' => 'emerald'],
                                        'dossier' => ['label' => 'DOSSIER DOCUMENTAIRE', 'color' => 'blue'],
                                        'comparatif' => ['label' => 'COMPARATIF DOCUMENTAIRE', 'color' => 'indigo'],
                                        'context' => ['label' => 'DOCUMENT DE CONTEXTE', 'color' => 'slate'],
                                        'synthesis' => ['label' => 'SYNTHÈSE LMALP', 'color' => 'purple'],
                                        default => ['label' => 'DOCUMENT', 'color' => 'amber'],
                                    };
                                }

                                // Pour le dossier public Séraphothèque, l'ancre documentaire canonique
                                // reprend le source_reference (identifiant de corpus public).
                                // Pour tous les autres sujets, on utilise l'ID interne afin de ne pas
                                // exposer la référence source dans le HTML public.
                                $docAnchor = $subject->isSeraphothequeDossier()
                                    ? 'doc-' . str_replace([':', '\/'], '-', (string) $doc->source_reference)
                                    : 'doc-' . $doc->id;
                            @endphp
                            <li id="{{ $docAnchor }}" class="grid grid-cols-1 sm:grid-cols-[auto_minmax(0,1fr)_auto] items-start gap-3 bg-slate-50 rounded-md border border-slate-200 p-3 scroll-mt-4">
                                <span class="text-2xl select-none shrink-0" title="Extension: {{ $doc->extension() }}">
                                    {{ $doc->icon() }}
                                </span>
                                <div class="min-w-0">
                                    <h4 class="text-base font-semibold text-slate-900 leading-snug mb-1 break-words">
                                        {{ $doc->title ?: $doc->filename }}
                                    </h4>
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-{{ $docTypeBadge['color'] }}-100 text-{{ $docTypeBadge['color'] }}-700 border border-{{ $docTypeBadge['color'] }}-200 uppercase tracking-wide font-medium">
                                            {{ $docTypeBadge['label'] }}
                                        </span>
                                        @if($doc->redacted)
                                            <span class="text-xs px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 border border-amber-200" data-redacted-badge>Version expurgée</span>
                                        @endif
                                        <span class="hidden sm:inline text-xs text-slate-400 whitespace-nowrap">{{ $doc->humanSize() }}</span>
                                    </div>
                                    <div class="text-xs text-slate-500 mb-2 hidden sm:flex flex-wrap gap-x-3 gap-y-1">
                                        @if($doc->document_date)
                                            <span>Date : {{ $doc->document_date->format('d/m/Y') }}</span>
                                        @endif
                                        @if($doc->document_type && ! str_contains($docTypeLower, 'dossier documentaire') && ! str_contains($docTypeLower, 'source primaire') && ! str_contains($docTypeLower, 'synthèse lmalp') && ! str_contains($docTypeLower, 'contexte'))
                                            <span>Nature : {{ $doc->document_type }}</span>
                                        @endif
                                        @if($doc->author)
                                            <span>Émetteur : {{ $doc->author }}</span>
                                        @endif
                                        @if($doc->recipient)
                                            <span>Destinataire : {{ $doc->recipient }}</span>
                                        @endif
                                    </div>
                                    @if($doc->document_date)
                                        <div class="text-xs text-slate-500 mb-2 sm:hidden">
                                            Date : {{ $doc->document_date->format('d/m/Y') }}
                                        </div>
                                    @endif
                                    @if($doc->description)
                                        <p class="text-xs text-slate-600 mt-0.5">{{ $doc->description }}</p>
                                    @endif
                                    @if($doc->establishes || $doc->limitations)
                                        <div class="text-xs text-slate-600 mt-1 space-y-1">
                                            @if($doc->establishes)
                                                <p><span class="font-medium">Établit :</span> {{ $doc->establishes }}</p>
                                            @endif
                                            @if($doc->limitations)
                                                <p><span class="font-medium">N'établit pas :</span> {{ $doc->limitations }}</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto shrink-0">
                                    @if($doc->hasStoredFile())
                                        @if($doc->isEmail())
                                            <a href="{{ route('subjects.documents.email', [$subject->slug, $doc->id]) }}" class="inline-flex items-center justify-center text-sm text-emerald-700 hover:text-emerald-900 font-medium border border-emerald-200 rounded-md px-3 py-1.5 bg-emerald-50 w-full sm:w-auto" data-testid="btn-doc-email">
                                                Lire le message
                                            </a>
                                        @else
                                            <a href="{{ $doc->url() }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center text-sm text-emerald-700 hover:text-emerald-900 font-medium border border-emerald-200 rounded-md px-3 py-1.5 bg-emerald-50 w-full sm:w-auto" data-testid="btn-doc-view">
                                                Consulter / Ouvrir
                                            </a>
                                            @if(! in_array($doc->extension(), ['html','md','htm','markdown']) && ! str_contains($docTypeLower, 'dossier documentaire'))
                                                <a href="{{ $doc->downloadUrl() }}" class="inline-flex items-center justify-center text-sm text-slate-600 hover:text-slate-900 border border-slate-300 rounded-md px-3 py-1.5 bg-white w-full sm:w-auto" download title="Télécharger" data-testid="btn-doc-download">
                                                    Télécharger
                                                </a>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </section>
@endif
