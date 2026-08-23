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
@endphp
@if($visibleDocs->count() > 0)
    <section id="documents" class="bg-white rounded-lg border border-slate-200 p-6 mb-8">
        <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M6 14h12M3 6h18m-1 14l-4-4h-6l-4 4m-2 0h6"/></svg>
            Sources et documents
        </h2>
        <ul class="space-y-3">
            @foreach($visibleDocs as $doc)
                <li class="flex items-start gap-3 bg-slate-50 rounded-md border border-slate-200 p-3">
                    <span class="text-2xl select-none" title="Extension: {{ $doc->extension() }}">
                        {{ $doc->icon() }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <span class="text-sm font-medium text-slate-900 truncate">
                                {{ $doc->title ?: $doc->filename }}
                            </span>
                            <span class="text-xs text-slate-400 whitespace-nowrap">{{ $doc->humanSize() }}</span>
                            @if($doc->redacted)
                                <span class="text-xs px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 border border-amber-200" data-redacted-badge>Version expurgée</span>
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
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 uppercase tracking-wide">{{ $doc->category }}</span>
                            <span class="text-xs text-slate-400">Ajouté le {{ $doc->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($doc->hasStoredFile())
                            <a href="{{ $doc->url() }}" target="_blank" rel="noopener noreferrer" class="text-sm text-emerald-700 hover:text-emerald-900 font-medium border border-emerald-200 rounded-md px-2 py-1 bg-emerald-50" data-testid="btn-doc-view">
                                Consulter / Ouvrir
                            </a>
                            <a href="{{ $doc->downloadUrl() }}" class="text-sm text-slate-500 hover:text-slate-900 border border-slate-300 rounded-md px-2 py-1 bg-white" download title="Télécharger" data-testid="btn-doc-download">
                                Télécharger
                            </a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endif
