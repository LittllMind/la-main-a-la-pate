<x-guest-layout>
    <h2 class="text-2xl font-bold mb-6 bg-gradient-to-r from-violet-400 to-pink-400 bg-clip-text text-transparent text-center">
        Inscription
    </h2>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nom')" class="text-gray-300" />
            <x-text-input id="name" class="block mt-1 w-full bg-gray-700 border-gray-600 text-gray-100 focus:border-violet-500 focus:ring focus:ring-violet-500/50" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" class="text-gray-300" />
            <x-text-input id="email" class="block mt-1 w-full bg-gray-700 border-gray-600 text-gray-100 focus:border-violet-500 focus:ring focus:ring-violet-500/50" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Mot de passe')" class="text-gray-300" />

            <x-text-input id="password" class="block mt-1 w-full bg-gray-700 border-gray-600 text-gray-100 focus:border-violet-500 focus:ring focus:ring-violet-500/50"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" class="text-gray-300" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full bg-gray-700 border-gray-600 text-gray-100 focus:border-violet-500 focus:ring focus:ring-violet-500/50"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <a class="underline text-sm text-gray-400 hover:text-violet-400 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 focus:ring-offset-gray-800" href="{{ route('login') }}">
                {{ __('Déjà inscrit ?') }}
            </a>

            <button type="submit" class="ms-4 px-6 py-2 bg-gradient-to-r from-violet-600 to-pink-600 text-white font-semibold rounded-lg shadow-lg hover:from-violet-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 focus:ring-offset-gray-800 transition-all duration-200">
                {{ __("S'inscrire") }}
            </button>
        </div>
    </form>
</x-guest-layout>
