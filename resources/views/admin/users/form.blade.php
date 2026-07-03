@php
$roles = $roles ?? [];
@endphp

<form method="POST" action="{{ $route }}" class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    @csrf
    @method($method)

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nom complet</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="255" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
            @error('name')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="pseudonyme" class="block text-sm font-medium text-slate-700 mb-1">Pseudonyme public</label>
            <input id="pseudonyme" name="pseudonyme" type="text" value="{{ old('pseudonyme', $user->pseudonyme) }}" maxlength="255" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
            @error('pseudonyme')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
            @error('email')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="commune" class="block text-sm font-medium text-slate-700 mb-1">Commune (optionnel)</label>
            <input id="commune" name="commune" type="text" value="{{ old('commune', $user->commune) }}" maxlength="255" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
            @error('commune')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mb-5">
        <label for="role" class="block text-sm font-medium text-slate-700 mb-1">Rôle</label>
        <select id="role" name="role" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm bg-white">
            @foreach($roles as $key => $label)
                <option value="{{ $key }}" {{ old('role', $user->role) === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('role')
            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    @if($method === 'POST')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Mot de passe</label>
                <input id="password" name="password" type="password" autocomplete="new-password" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                @error('password')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirmer le mot de passe</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
            </div>
        </div>
    @else
        <div class="mb-5 p-4 bg-slate-50 rounded-md border border-slate-200">
            <p class="text-sm text-slate-700 mb-2">Réinitialiser le mot de passe (laisser vide pour ne pas changer)</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <input name="password" type="password" autocomplete="new-password" placeholder="Nouveau mot de passe" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                    @error('password')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <input name="password_confirmation" type="password" autocomplete="new-password" placeholder="Confirmer" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                </div>
            </div>
        </div>
    @endif

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">Enregistrer</button>
        <a href="{{ route('admin.users.index') }}" class="text-slate-500 text-sm hover:text-slate-800">Annuler</a>
    </div>
</form>
