@extends('layouts.public')

@section('title', 'Connexion')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-lg border border-slate-200 p-6 md:p-8">
        <h2 class="text-xl font-bold text-slate-900 text-center mb-6">Connexion</h2>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Identifiant -->
            <div class="relative">
                <x-input-label for="login" :value="__('Identifiant')" />
                <x-text-input id="login" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('login')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Mot de passe')" />
                <x-text-input id="password" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Remember Me -->
            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-900" name="remember">
                    <span class="ms-2 text-sm text-slate-600">{{ __('Se souvenir de moi') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-between mt-6">
                @if (Route::has('password.request'))
                    <a class="text-sm text-slate-500 hover:text-slate-900 underline" href="{{ route('password.request') }}">
                        {{ __('Mot de passe oublie ?') }}
                    </a>
                @endif

                <button type="submit" class="ms-4 px-5 py-2 bg-slate-900 text-white text-sm font-medium rounded-md hover:bg-slate-700 transition">
                    {{ __('Se connecter') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
