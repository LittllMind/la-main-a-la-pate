@extends('layouts.public')

@section('title', 'Images — ' . $subject->title)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Galerie du sujet</h1>
            <p class="text-slate-600 text-sm">{{ $subject->title }}</p>
        </div>
        <a href="{{ route('subjects.show', $subject->slug) }}" class="text-slate-500 text-sm hover:text-slate-800">Retour au sujet</a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 text-emerald-800 rounded-md text-sm">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('subjects.images.store', $subject->slug) }}" enctype="multipart/form-data" class="bg-white rounded-lg border border-slate-200 p-6 mb-6">
        @csrf
        <div class="mb-4">
            <label for="image" class="block text-sm font-medium text-slate-700 mb-1">Ajouter une image</label>
            <input id="image" name="image" type="file" accept="image/*" class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
            @error('image')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="mb-4">
            <label for="alt" class="block text-sm font-medium text-slate-700 mb-1">Légende / texte alternatif</label>
            <input id="alt" name="alt" type="text" maxlength="255" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
        </div>
        <button type="submit" class="bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">Téléverser</button>
    </form>

    @if($subject->images->count() === 0)
        <p class="text-slate-500 text-sm">Aucune image attachée.</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($subject->images as $image)
                <div id="image-{{ $image->id }}" class="bg-white rounded-lg border border-slate-200 p-4">
                    <img src="{{ $image->url() }}" alt="{{ $image->alt }}" class="rounded-lg border border-slate-200 w-full h-48 object-cover mb-3">
                    <form method="POST" action="{{ route('subjects.images.update', [$subject->slug, $image]) }}" class="space-y-2">
                        @csrf
                        @method('PATCH')
                        <input name="alt" value="{{ old('alt', $image->alt) }}" class="w-full border border-slate-300 rounded-md px-2 py-1 text-sm">
                        <input name="position" type="number" min="0" value="{{ $image->position }}" class="w-24 border border-slate-300 rounded-md px-2 py-1 text-sm">
                        <div class="flex gap-2">
                            <button type="submit" class="text-emerald-700 text-sm hover:text-emerald-900">Mettre à jour</button>
                            <button type="button" onclick="copyMarkdown('![{{ addslashes($image->alt) }}]({{ $image->url() }})')" class="text-slate-500 text-sm hover:text-slate-800">Copier markdown</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('subjects.images.destroy', [$subject->slug, $image]) }}" class="mt-2" onsubmit="return confirm('Supprimer cette image ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 text-sm hover:text-red-800">Supprimer</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    function copyMarkdown(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Markdown copié !');
        });
    }
</script>
@endsection
