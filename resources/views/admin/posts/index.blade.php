@extends('layouts.public')

@section('title', 'Gestion des articles')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Articles</h1>
        <a href="{{ route('admin.posts.create') }}" class="bg-slate-900 text-white px-4 py-2 rounded-md text-sm hover:bg-slate-700">+ Nouvel article</a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-slate-700">Titre</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-700">Statut</th>
                    <th class="text-left px-4 py-3 font-medium text-slate-700">Date</th>
                    <th class="text-right px-4 py-3 font-medium text-slate-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($posts as $post)
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-900">{{ $post->title }}</div>
                        <div class="text-xs text-slate-500">{{ $post->category->name ?? '-' }}</div>
                    </td>
                    <td class="px-4 py-3">
                        @if($post->status === 'published')
                            <span class="text-green-700 bg-green-50 px-2 py-0.5 rounded text-xs">Publie</span>
                        @elseif($post->status === 'pending')
                            <span class="text-amber-700 bg-amber-50 px-2 py-0.5 rounded text-xs">En attente</span>
                        @else
                            <span class="text-slate-600 bg-slate-100 px-2 py-0.5 rounded text-xs">Brouillon</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-500">{{ $post->created_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-right space-x-2">
                        @if($post->status !== 'published')
                        <form method="POST" action="{{ route('admin.posts.publish', $post) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-green-600 hover:text-green-800 text-xs">Publier</button>
                        </form>
                        @endif
                        <a href="{{ route('admin.posts.edit', $post) }}" class="text-slate-600 hover:text-slate-900 text-xs">Modifier</a>
                        <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" class="inline" onsubmit="return confirm('Supprimer ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs">Supprimer</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $posts->links() }}</div>
</div>
@endsection
