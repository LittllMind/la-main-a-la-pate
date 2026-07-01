@extends('layouts.admin')

@section('title', 'Sections du Hall')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Sections du Hall</h1>
            <p class="text-slate-600 text-sm">Ordre, contenu et visibilité des sections affichées sur la page d'accueil.</p>
        </div>
        <a href="{{ route('admin.sections.create') }}" class="inline-flex items-center justify-center bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">
            + Nouvelle section
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 text-emerald-800 rounded-md text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Clé</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Titre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Sous-titre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Active</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($sections as $section)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ $section->position }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $section->key }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">{{ $section->title }}</td>
                        <td class="px-6 py-4 text-sm text-slate-500 max-w-xs truncate">{{ $section->subtitle }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($section->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">Actif</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">Inactif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="{{ route('admin.sections.toggle', $section->id) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-slate-600 hover:text-emerald-700">{{ $section->is_active ? 'Désactiver' : 'Activer' }}</button>
                                </form>
                                <a href="{{ route('admin.sections.edit', $section->id) }}" class="text-emerald-600 hover:text-emerald-800">Modifier</a>
                                <form method="POST" action="{{ route('admin.sections.destroy', $section->id) }}" class="inline" onsubmit="return confirm('Supprimer cette section ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-slate-500">Aucune section définie.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
