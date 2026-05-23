<x-guest-layout>
    <h2 class="text-xl font-bold text-slate-900 text-center mb-6">
        Inscription
    </h2>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nom complet')" />
            <x-text-input id="name" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Pseudonyme -->
        <div class="mt-4">
            <x-input-label for="pseudonyme" :value="__('Pseudonyme public')" />
            <x-text-input id="pseudonyme" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="text" name="pseudonyme" :value="old('pseudonyme')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('pseudonyme')" class="mt-2" />
            <p class="text-xs text-slate-500 mt-1">Ce nom sera visible par les autres membres.</p>
        </div>

        <!-- Commune -->
        <div class="mt-4">
            <x-input-label for="commune" :value="__('Commune (optionnel)')" />
            <x-text-input id="commune" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="text" name="commune" :value="old('commune')" autocomplete="address-level2" />
            <x-input-error :messages="$errors->get('commune')" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Mot de passe')" />
            <x-text-input id="password" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- RGPD -->
        <div class="mt-4 flex items-start gap-2">
            <input type="checkbox" name="rgpd_consent" id="rgpd_consent" value="1" required class="mt-1 h-4 w-4 border-slate-300 rounded text-slate-900 focus:ring-slate-900">
            <label for="rgpd_consent" class="text-xs text-slate-600">
                J'accepte que mes donnees soient traitees conformement a la politique de confidentialite. Je peux demander la suppression de mon compte a tout moment.
            </label>
        </div>
        <x-input-error :messages="$errors->get('rgpd_consent')" class="mt-2" />

        <div class="flex items-center justify-between mt-6">
            <a class="text-sm text-slate-500 hover:text-slate-900 underline" href="{{ route('login') }}">
                {{ __('Deja inscrit ?') }}
            </a>

            <button type="submit" class="ms-4 px-5 py-2 bg-slate-900 text-white text-sm font-medium rounded-md hover:bg-slate-700 transition">
                {{ __("S'inscrire") }}
            </button>
        </div>
    </form>
</x-guest-layout>
