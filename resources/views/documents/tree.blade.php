@extends('layouts.public')

@section('title', $mode === 'subjects' ? 'Arborescence des sujets — La Main à la Pâte' : 'Arborescence documentaire — La Main à la Pâte')

@php
    $isSubjects = $mode === 'subjects';
@endphp

@section('content')
<script id="tree-initial-data" type="application/json" data-api="{{ $apiUrl }}" data-mode="{{ $mode }}">@json($initialCategories ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS)</script>
<div class="max-w-6xl mx-auto px-4 py-8" x-data="documentTree('{{ $apiUrl }}')" x-init="init()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div class="min-w-0">
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">{{ $isSubjects ? 'Arborescence des sujets' : 'Arborescence documentaire' }}</h1>
            <p class="text-slate-500 text-sm mt-1">
                {{ $isSubjects
                    ? 'Tous les sujets classés par thème. Seuls les documents accessibles selon vos droits apparaissent.'
                    : 'Sources, annexes et OCR classés par thème. Seuls les éléments accessibles selon vos droits apparaissent.'
                }}
            </p>
        </div>
        @if($isSubjects)
            @can('create', App\Models\Subject::class)
                <a href="{{ route('subjects.create') }}" class="inline-flex items-center justify-center bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition shrink-0">
                    + Nouveau sujet
                </a>
            @endcan
        @endif
    </div>

    {{-- Sticky filters --}}
    <div class="sticky top-0 z-30 bg-slate-50/95 backdrop-blur border-y border-slate-200 -mx-4 px-4 py-3 mb-6">
        <div class="flex flex-col lg:flex-row gap-3 lg:items-center">
            <div class="flex items-center gap-2 shrink-0">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Filtres</span>
                <span class="hidden sm:inline-flex items-center justify-center px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-700 text-xs font-medium">{{ $totalSubjects ?? 0 }} sujet{{ ($totalSubjects ?? 0) > 1 ? 's' : '' }}</span>
            </div>

            <div class="flex-1 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input type="text" x-model="search" :placeholder="$isSubjects ? 'Rechercher un sujet...' : 'Rechercher un sujet ou un document...'"
                           class="w-full border border-slate-300 rounded-md pl-9 pr-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                           @input="applyFilter()" />
                </div>

                <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                    <button type="button" @click="statusFilter = 'all'; applyFilter()"
                            :class="statusFilter === 'all' ? 'bg-slate-800 text-white' : 'bg-white text-slate-700 hover:bg-slate-100'"
                            class="px-3 py-2 rounded-md text-sm font-medium border border-slate-200 transition shrink-0">Tous</button>
                    <button type="button" @click="statusFilter = 'published'; applyFilter()"
                            :class="statusFilter === 'published' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-700 hover:bg-emerald-50 border-slate-200'"
                            class="px-3 py-2 rounded-md text-sm font-medium border transition shrink-0 inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500" :class="statusFilter === 'published' ? 'bg-white' : 'bg-emerald-500'"></span> Publiés
                    </button>
                    <button type="button" @click="statusFilter = 'draft'; applyFilter()"
                            :class="statusFilter === 'draft' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-slate-700 hover:bg-amber-50 border-slate-200'"
                            class="px-3 py-2 rounded-md text-sm font-medium border transition shrink-0 inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full" :class="statusFilter === 'draft' ? 'bg-white' : 'bg-amber-400'"></span> Brouillons
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Publié</span>
            <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400"></span> Brouillon</span>
            <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-300"></span> Document joint</span>
            @if(! $isSubjects)
                <span class="inline-flex items-center gap-1"><span class="text-xs">🔒</span> Téléchargement contrôlé</span>
            @endif
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-slate-500 text-sm" x-cloak>Chargement...</div>

    <!-- Empty -->
    <div x-show="emptyState" class="text-center py-16 bg-white rounded-lg border border-slate-200" x-cloak>
        <div class="text-4xl mb-3">🌳</div>
        <p class="text-slate-500 font-medium">Aucun élément accessible.</p>
        <p x-show="search" class="text-slate-400 text-sm mt-1">Essayez un autre terme de recherche.</p>
    </div>

    <!-- Tree -->
    <div x-show="readyState" class="space-y-4" x-cloak>
        <template x-for="category in filteredCategories" :key="category.id">
            <div class="rounded-xl border border-slate-200 overflow-hidden bg-white shadow-sm">
                {{-- Category header --}}
                <button type="button" @click="toggleCategory(category.id)"
                        class="w-full flex items-center gap-3 px-4 py-3.5 text-left transition hover:brightness-[0.98]"
                        :style="category.color ? 'background-color: ' + category.color + '26' : ''">
                    <span class="text-xl" x-text="category.icon || '📁'"></span>
                    <span class="font-semibold text-slate-800" x-text="category.name"></span>
                    <span class="ml-auto text-xs font-medium text-slate-600 bg-white/60 px-2 py-0.5 rounded-full"
                          x-text="countSubjects(category) + ' sujet' + (countSubjects(category) > 1 ? 's' : '')"></span>
                    <svg :class="isCategoryOpen(category.id) ? 'rotate-90' : ''" class="w-5 h-5 text-slate-500 transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                {{-- Sub-categories --}}
                <div x-show="isCategoryOpen(category.id)" class="px-4 py-4 space-y-4" x-cloak>
                    <template x-for="sub in category.subCategories" :key="sub.id">
                        <div class="border-l-4 border-slate-200 pl-4" :style="category.color ? 'border-color: ' + category.color : ''">
                            <div class="flex items-center justify-between gap-3 py-1 group/sub">
                                <button type="button" @click="toggleSub(sub.id)"
                                        class="flex items-center gap-2 text-left flex-1 min-w-0">
                                    <span class="text-sm font-semibold text-slate-700 truncate" x-text="sub.name"></span>
                                    <span class="text-xs text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded-full"
                                          x-text="sub.subjects.length + ' sujet' + (sub.subjects.length > 1 ? 's' : '')"></span>
                                    <svg :class="isSubOpen(sub.id) ? 'rotate-90' : ''" class="w-4 h-4 text-slate-400 transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                                @if($isSubjects)
                                    @can('create', App\Models\Subject::class)
                                        <a :href="'/sujets/creer?category_id=' + category.id + '&sub_category_id=' + sub.id"
                                           x-show="isSubOpen(sub.id)"
                                           x-cloak
                                           class="opacity-0 group-hover/sub:opacity-100 focus:opacity-100 transition inline-flex items-center gap-1 text-xs px-2.5 py-1.5 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-medium shrink-0">
                                            + Sujet
                                        </a>
                                    @endcan
                                @endif
                            </div>

                            {{-- Subjects list --}}
                            <div x-show="isSubOpen(sub.id)" class="mt-2 space-y-1.5" x-cloak>
                                <template x-for="subject in sub.subjects" :key="subject.id">
                                    <div class="rounded-lg border border-slate-200 bg-white overflow-hidden hover:border-slate-300 transition">
                                        <a :href="'/sujets/' + subject.slug" class="flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50">
                                            {{-- Status dot --}}
                                            <span class="shrink-0 w-2.5 h-2.5 rounded-full"
                                                  :class="subject.status === 'published' ? 'bg-emerald-500' : 'bg-amber-400'"
                                                  :title="subject.status === 'published' ? 'Publié' : 'Brouillon'"></span>

                                            {{-- Title + meta --}}
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="text-sm font-medium text-slate-800 truncate" x-text="subject.title"></span>
                                                    <span x-show="subject.can_update" class="text-xs px-1.5 py-0.5 rounded bg-slate-200 text-slate-600" title="Vous pouvez modifier">✎</span>
                                                </div>
                                                <div class="mt-0.5 flex items-center gap-3 text-xs text-slate-400">
                                                    <span x-text="subject.word_count + ' mots'"></span>
                                                    <span x-show="subject.doc_count > 0" class="inline-flex items-center gap-1 text-slate-500">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                                        </svg>
                                                        <span x-text="subject.doc_count + ' doc' + (subject.doc_count > 1 ? 's' : '')"></span>
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- Arrow --}}
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-slate-300 shrink-0">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                            </svg>
                                        </a>

                                        @if(! $isSubjects)
                                            <div class="px-3 pb-2">
                                                <template x-for="doc in subject.documents" :key="doc.id">
                                                    <div class="flex items-start gap-2 py-1.5 border-t border-slate-100 first:border-t-0">
                                                        <span class="text-lg leading-none" x-text="doc.icon"></span>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-baseline gap-2 flex-wrap">
                                                                <a :href="doc.download_url" class="text-sm font-medium text-emerald-700 hover:text-emerald-900 truncate">
                                                                    <span x-text="doc.title"></span>
                                                                    <span class="text-xs text-slate-400" x-text="'.' + doc.extension"></span>
                                                                </a>
                                                                <span class="text-xs text-slate-400 whitespace-nowrap" x-text="doc.size"></span>
                                                                <span class="text-xs px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 uppercase" x-text="doc.category"></span>
                                                                <span class="text-xs" title="Téléchargement sécurisé">🔒</span>
                                                            </div>
                                                            <p x-show="doc.description" class="text-xs text-slate-500 truncate" x-text="doc.description"></p>
                                                            <p class="text-xs text-slate-400" x-text="'Ajouté le ' + doc.created_at"></p>
                                                        </div>
                                                    </div>
                                                </template>

                                                <div x-show="subject.documents.length === 0" class="text-xs text-slate-400 italic py-1">Aucun document attaché.</div>
                                            </div>
                                        @endif
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection