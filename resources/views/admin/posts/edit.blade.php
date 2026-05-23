@extends('layouts.public')

@section('title', 'Modifier article')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">Modifier l'article</h1>

    <form method="POST" action="{{ route('admin.posts.update', $post) }}" class="bg-white rounded-lg border border-slate-200 p-6">
        @csrf @method('PUT')
        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-1">Titre</label>
            <input type="text" name="title" value="{{ $post->title }}" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-1">Categorie</label>
            <select name="category_id" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ $post->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-1">Extrait</label>
            <textarea name="excerpt" rows="2" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">{{ $post->excerpt }}</textarea>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-1">Contenu</label>
            <textarea name="content" rows="8" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">{{ $post->content }}</textarea>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-1">Statut</label>
            <select name="status" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                <option value="draft" {{ $post->status == 'draft' ? 'selected' : '' }}>Brouillon</option>
                <option value="pending" {{ $post->status == 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="published" {{ $post->status == 'published' ? 'selected' : '' }}>Publie</option>
            </select>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-md text-sm hover:bg-slate-700">Mettre a jour</button>
            <a href="{{ route('admin.posts.index') }}" class="text-slate-500 text-sm hover:text-slate-900">Annuler</a>
        </div>
    </form>
</div>
@endsection
