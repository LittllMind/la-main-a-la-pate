@extends('layouts.admin')

@section('title', 'Gestion des articles')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-white">Articles du blog</h1>
        <a href="{{ route('admin.posts.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium transition">
            + Nouvel article
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-900/50 border border-green-700 text-green-300 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-700/50 text-gray-400 text-sm uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4">Titre</th>
                    <th class="px-6 py-4">Statut</th>
                    <th class="px-6 py-4">Publication</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($posts as $post)
                <tr class="hover:bg-gray-700/30 transition">
                    <td class="px-6 py-4">
                        <div class="font-medium text-white">{{ Str::limit($post->title, 60) }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ $post->slug }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($post->isPublished())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900 text-green-300">
                                Publié
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-900 text-yellow-300">
                                Brouillon
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-400">
                        {{ $post->published_at ? $post->published_at->format('d/m/Y H:i') : '—' }}
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="{{ route('admin.posts.edit', $post) }}" class="text-purple-400 hover:text-purple-300 transition" title="Modifier">
                            ✏️
                        </a>
                        <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer cet article ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-300 transition" title="Supprimer">
                                🗑️
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                        Aucun article pour le moment.
                        <a href="{{ route('admin.posts.create') }}" class="text-purple-400 hover:underline ml-1">Créer le premier</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $posts->links() }}
    </div>
</div>
@endsection
