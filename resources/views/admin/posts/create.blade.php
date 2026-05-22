@extends('layouts.admin')

@section('title', 'Nouvel article')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-white mb-8">Nouvel article</h1>

    <form action="{{ route('admin.posts.store') }}" method="POST" class="space-y-6">
        @csrf

        <div>
            <label for="title" class="block text-sm font-medium text-gray-400 mb-2">Titre *</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}" required
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none"
                placeholder="Titre de l'article">
            @error('title')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="slug" class="block text-sm font-medium text-gray-400 mb-2">Slug (optionnel — généré auto depuis le titre)</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none"
                placeholder="mon-super-article">
            @error('slug')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="excerpt" class="block text-sm font-medium text-gray-400 mb-2">Résumé (optionnel)</label>
            <textarea name="excerpt" id="excerpt" rows="3"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none resize-y"
                placeholder="Un court résumé visible dans la liste des articles...">{{ old('excerpt') }}</textarea>
            @error('excerpt')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="content" class="block text-sm font-medium text-gray-400 mb-2">Contenu *</label>
            <textarea name="content" id="content" rows="20" required
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none resize-y font-mono text-sm"
                placeholder="Le contenu de l'article...">{{ old('content') }}</textarea>
            @error('content')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-400 mb-2">Statut</label>
                <select name="status" id="status"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none">
                    <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>📝 Brouillon</option>
                    <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>🟢 Publié</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="published_at" class="block text-sm font-medium text-gray-400 mb-2">Date de publication</label>
                <input type="datetime-local" name="published_at" id="published_at" value="{{ old('published_at') }}"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none">
                @error('published_at')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center gap-4 pt-4">
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-lg font-medium transition">
                Créer l'article
            </button>
            <a href="{{ route('admin.posts.index') }}" class="text-gray-400 hover:text-white transition">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection
