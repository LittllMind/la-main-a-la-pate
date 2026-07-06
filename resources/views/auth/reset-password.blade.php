@extends('layouts.public')

@section('title', 'Nouveau mot de passe')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-lg border border-slate-200 p-6 md:p-8">
        <h2 class="text-xl font-bold text-slate-900 text-center mb-6">Nouveau mot de passe</h2>

        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password" :value="__('Mot de passe')" />
                <x-text-input id="password" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full border-slate-300 focus:border-slate-900 focus:ring-slate-900" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end mt-6">
                <button type="submit" class="px-5 py-2 bg-slate-900 text-white text-sm font-medium rounded-md hover:bg-slate-700 transition">
                    Reinitialiser le mot de passe
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
