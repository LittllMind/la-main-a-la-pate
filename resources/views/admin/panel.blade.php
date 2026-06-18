@extends('layouts.admin')

@section('title', 'Panneau administrateur')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Panneau d'administration</h1>
        <p class="text-slate-600 text-sm">Acces a toutes les routes existantes et aux outils de gestion.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <a href="{{ route('admin.routes') }}" class="bg-white rounded-xl border border-slate-200 p-6 hover:border-emerald-400 hover:shadow-sm transition group">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 bg-emerald-50 rounded-lg text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V3m0 18v-3.75m6-12V3m0 18v-3.75m-9 3.75h12" />
                    </svg>
                </div>
                <span class="text-xs font-medium text-slate-400 bg-slate-50 px-2 py-1 rounded">{{ collect($routeGroups)->sum(fn($routes) => count($routes)) }} routes</span>
            </div>
            <h2 class="font-semibold text-slate-900 group-hover:text-emerald-700">Routes</h2>
            <p class="text-sm text-slate-500 mt-1">Liste complete triee par groupe, avec liens directs.</p>
        </a>

        <a href="{{ route('admin.posts.index') }}" class="bg-white rounded-xl border border-slate-200 p-6 hover:border-emerald-400 hover:shadow-sm transition group">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 bg-blue-50 rounded-lg text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9v12m0 0h-3.75m3.75 0v1.5m0-1.5H21M3 19.5v-12a2.25 2.25 0 012.25-2.25h13.5a2.25 2.25 0 012.25 2.25v12" />
                    </svg>
                </div>
            </div>
            <h2 class="font-semibold text-slate-900 group-hover:text-emerald-700">Articles / Hall</h2>
            <p class="text-sm text-slate-500 mt-1">Gestion des actualites publiees sur le Hall.</p>
        </a>

        <a href="/sujets" class="bg-white rounded-xl border border-slate-200 p-6 hover:border-emerald-400 hover:shadow-sm transition group">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 bg-amber-50 rounded-lg text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
            </div>
            <h2 class="font-semibold text-slate-900 group-hover:text-emerald-700">Sujets</h2>
            <p class="text-sm text-slate-500 mt-1">Documents de reference et fils de discussion.</p>
        </a>

        <a href="/communaute" class="bg-white rounded-xl border border-slate-200 p-6 hover:border-emerald-400 hover:shadow-sm transition group">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 bg-purple-50 rounded-lg text-purple-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m0 0a3 3 0 00-4.682 2.72M3 18.72a9.094 9.094 0 003.741.479 3 3 0 004.682-2.72m0 0a3 3 0 00-4.682-2.72M12 12a3 3 0 100-6 3 3 0 000 6z" />
                    </svg>
                </div>
            </div>
            <h2 class="font-semibold text-slate-900 group-hover:text-emerald-700">Communaute</h2>
            <p class="text-sm text-slate-500 mt-1">Acces a l'espace communaute (masque en navigation publique).</p>
        </a>
    </div>
@endsection
