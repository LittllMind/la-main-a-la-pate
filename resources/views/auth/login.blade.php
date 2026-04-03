<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h2 class="text-2xl font-bold mb-6 bg-gradient-to-r from-violet-400 to-pink-400 bg-clip-text text-transparent text-center">
        Connexion
    </h2>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-gray-300" />
            <x-text-input id="email" class="block mt-1 w-full bg-gray-700 border-gray-600 text-gray-100 focus:border-violet-500 focus:ring focus:ring-violet-500/50" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Mot de passe')" class="text-gray-300" />

            <x-text-input id="password" class="block mt-1 w-full bg-gray-700 border-gray-600 text-gray-100 focus:border-violet-500 focus:ring focus:ring-violet-500/50"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-600 bg-gray-700 text-violet-500 shadow-sm focus:ring-violet-500" name="remember">
                <span class="ms-2 text-sm text-gray-400">{{ __('Se souvenir de moi') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-400 hover:text-violet-400 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    {{ __('Mot de passe oublié ?') }}
                </a>
            @endif

            <button type="submit" class="ms-3 px-6 py-2 bg-gradient-to-r from-violet-600 to-pink-600 text-white font-semibold rounded-lg shadow-lg hover:from-violet-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 focus:ring-offset-gray-800 transition-all duration-200">
                {{ __('Se connecter') }}
            </button>
        </div>
    </form>

    <div class="text-center mt-6">
        <p class="text-sm text-gray-400">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="text-violet-400 hover:text-violet-300 font-medium">
                S'inscrire
            </a>
        </p>
    </div>
</x-guest-layout>
