@extends('layouts.public')

@section('title', 'Mot de passe oublie')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-lg border border-slate-200 p-6 md:p-8">
        <h2 class="text-xl font-bold text-slate-900 text-center mb-2">Mot de passe oublie ?</h2>
        <p class="text-sm text-slate-500 text-center mb-6">
            Indiquez votre adresse e-mail et nous vous enverrons un lien de reinitialisation.
        </p>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="email" name="email" :value="old('email')" required autofocus />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between mt-6">
                <a href="{{ route('login') }}" class="text-sm text-slate-500 hover:text-slate-900 transition">
                    Retour a la connexion
                </a>

                <button type="submit" class="px-5 py-2 bg-slate-900 text-white text-sm font-medium rounded-md hover:bg-slate-700 transition">
                    Envoyer le lien
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
