@extends('layouts.public')

@section('title', 'Contact')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-8">
        <a href="{{ route('home') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Retour aux actualites</a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-6 md:p-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-4">Nous contacter</h1>
        <p class="text-slate-600 text-sm mb-6">
            Remplissez le formulaire ci-dessous. Nous vous repondrons dans les plus brefs delais.
        </p>

        <form method="POST" action="{{ route('contact.submit') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nom</label>
                <input type="text" name="name" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900" value="{{ old('name') }}">
                @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900" value="{{ old('email') }}">
                @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Sujet (optionnel)</label>
                <input type="text" name="subject" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900" value="{{ old('subject') }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Message</label>
                <textarea name="message" rows="5" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">{{ old('message') }}</textarea>
                @error('message') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-md text-sm hover:bg-slate-700 transition">
                Envoyer
            </button>
        </form>
    </div>
</div>
@endsection
