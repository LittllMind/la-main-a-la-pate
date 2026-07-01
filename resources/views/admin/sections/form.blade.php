<form method="POST" action="{{ $route }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf
    @method($method)

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
        <div>
            <label for="key" class="block text-sm font-medium text-slate-700 mb-1">Clé unique</label>
            <input id="key" name="key" type="text" value="{{ old('key', $section->key) }}" maxlength="120" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
            <p class="text-xs text-slate-500 mt-1">Identifiant machine de la section.</p>
            @error('key')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="position" class="block text-sm font-medium text-slate-700 mb-1">Position</label>
            <input id="position" name="position" type="number" min="0" value="{{ old('position', $section->position) }}" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
            @error('position')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mb-5">
        <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Titre</label>
        <input id="title" name="title" type="text" value="{{ old('title', $section->title) }}" maxlength="255" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
        @error('title')
            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-5">
        <label for="subtitle" class="block text-sm font-medium text-slate-700 mb-1">Sous-titre</label>
        <input id="subtitle" name="subtitle" type="text" value="{{ old('subtitle', $section->subtitle) }}" maxlength="255" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
        @error('subtitle')
            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-5">
        <label for="body" class="block text-sm font-medium text-slate-700 mb-1">Contenu HTML</label>
        <textarea id="body" name="body" rows="12" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm font-mono">{{ old('body', $section->body) }}</textarea>
        @error('body')
            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-6">
        <label class="inline-flex items-center">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $section->is_active) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            <span class="ml-2 text-sm text-slate-700">Section active</span>
        </label>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">Enregistrer</button>
        <a href="{{ route('admin.sections.index') }}" class="text-slate-500 text-sm hover:text-slate-800">Annuler</a>
    </div>
</form>
