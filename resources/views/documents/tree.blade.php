@extends('layouts.public')

@section('title', $mode === 'subjects' ? 'Arborescence des sujets — La Main à la Pâte' : 'Arborescence documentaire — La Main à la Pâte')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8" x-data="documentTree('{{ $apiUrl }}')" x-init="init()">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">{{ $mode === 'subjects' ? 'Arborescence des sujets' : 'Arborescence documentaire' }}</h1>
        <p class="text-slate-500 text-sm mt-1">
            {{ $mode === 'subjects'
                ? 'Tous les sujets classés par catégorie. Seuls les sujets accessibles selon vos droits apparaissent.'
                : 'Sources, annexes et OCR classés par catégorie. Seuls les sujets et documents accessibles selon vos droits apparaissent.'
            }}
        </p>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <input type="text" x-model="search" placeholder="Rechercher un sujet ou un document..."
               class="flex-1 border border-slate-300 rounded-md px-3 py-2 text-sm" @input="applyFilter()" />
        <select x-model="statusFilter" @change="applyFilter()" class="border border-slate-300 rounded-md px-3 py-2 text-sm">
            <option value="all">Tous les statuts</option>
            <option value="published">Publiés</option>
            <option value="draft">Brouillons</option>
        </select>
    </div>

    <div class="text-slate-500 text-sm mb-4">
        <span class="inline-flex items-center gap-1 mr-4"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Publié</span>
        <span class="inline-flex items-center gap-1 mr-4"><span class="w-2 h-2 rounded-full bg-amber-400"></span> Brouillon</span>
        @if($mode !== 'subjects')
            <span class="inline-flex items-center gap-1"><span class="text-xs">🔒</span> Téléchargement contrôlé</span>
        @endif
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-slate-500 text-sm" x-cloak>Chargement...</div>

    <!-- Empty -->
    <div x-show="emptyState" class="text-slate-500 text-sm" x-cloak>
        Aucun élément accessible.
    </div>

    <!-- Tree -->
    <div x-show="readyState" class="space-y-3" x-cloak>
        <template x-for="category in filteredCategories" :key="category.id">
            <div class="border border-slate-200 rounded-lg overflow-hidden bg-white">
                <button type="button" @click="toggleCategory(category.id)"
                        class="w-full flex items-center gap-2 px-4 py-3 bg-slate-50 hover:bg-slate-100 text-left">
                    <span class="text-lg" x-text="category.icon || '📁'"></span>
                    <span class="font-semibold text-slate-800" x-text="category.name"></span>
                    <span
                        class="ml-auto text-xs text-slate-500"
                        x-text="countSubjects(category) + ' sujet' + (countSubjects(category) > 1 ? 's' : '')"></span>
                    <svg :class="isCategoryOpen(category.id) ? 'rotate-90' : ''" class="w-4 h-4 text-slate-400 transition ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <div x-show="isCategoryOpen(category.id)" class="px-4 py-3 space-y-3" x-cloak>
                    <template x-for="sub in category.subCategories" :key="sub.id">
                        <div class="border-l-2 border-slate-200 pl-4">
                            <div class="flex items-center justify-between py-1">
                                <button type="button" @click="toggleSub(sub.id)"
                                        class="flex items-center gap-2 text-left">
                                    <span class="text-sm font-medium text-slate-700" x-text="sub.name"></span>
                                    <span class="text-xs text-slate-400" x-text="sub.subjects.length + ' sujet' + (sub.subjects.length > 1 ? 's' : '')"></span>
                                    <svg :class="isSubOpen(sub.id) ? 'rotate-90' : ''" class="w-3 h-3 text-slate-400 transition ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                                <a :href="'/sujets/creer?category_id=' + category.id + '&sub_category_id=' + sub.id"
                                   class="text-xs px-2 py-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition shrink-0">
                                    + Sujet
                                </a>
                            </div>

                            <div x-show="isSubOpen(sub.id)" class="mt-2 space-y-2" x-cloak>
                                <template x-for="subject in sub.subjects" :key="subject.id">
                                    <div class="bg-slate-50 rounded-md border border-slate-200 overflow-hidden">
                                        <a :href="'/sujets/' + subject.slug" class="flex items-center gap-2 px-3 py-2 hover:bg-slate-100">
                                            <span class="w-2 h-2 rounded-full" :class="subject.status === 'published' ? 'bg-emerald-500' : 'bg-amber-400'"></span>
                                            <span class="text-sm font-medium text-slate-800" x-text="subject.title"></span>
                                            <span class="text-xs text-slate-400" x-text="'(' + subject.word_count + ' mots)'"></span>
                                            <span x-show="subject.can_update" class="text-xs px-1.5 py-0.5 rounded bg-slate-200 text-slate-600">✎</span>
                                            @if($mode === 'subjects')
                                                <span class="ml-auto text-xs text-slate-400" x-text="subject.doc_count + ' doc' + (subject.doc_count > 1 ? 's' : '')"></span>
                                            @endif
                                        </a>

                                        @if($mode !== 'subjects')
                                            <div class="px-3 pb-2">
                                                <template x-for="doc in subject.documents" :key="doc.id">
                                                    <div class="flex items-start gap-2 py-1.5 border-t border-slate-200 first:border-t-0">
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
