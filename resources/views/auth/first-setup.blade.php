@extends('layouts.public')

@section('title', 'Configuration de votre profil')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">
    <div class="bg-white rounded-lg border border-slate-200 p-6 md:p-8">
        <h2 class="text-xl font-bold text-slate-900 mb-2">Bienvenue sur La Main à la Pâte !</h2>

        <!-- Bulle d'aide -->
        <div class="bg-sky-50 border border-sky-200 rounded-lg p-4 mb-6">
            <div class="flex gap-3">
                <div class="text-2xl">💡</div>
                <div class="text-sm text-sky-800 space-y-1">
                    <p>C'est votre première connexion. Complétez votre profil pour pouvoir accéder à la plateforme.</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        <li>Vous pouvez changer votre <b>identifiant</b> (si vous le souhaitez) et votre <b>adresse e-mail</b></li>
                        <li>Choisissez un nouveau <b>mot de passe</b> personnel</li>
                        <li>Mettez à jour le <b>nom affiché</b> et votre <b>pseudo</b></li>
                        <li>Votre e-mail ne sera jamais partagée — elle sert uniquement à la connexion sécurisée</li>
                    </ul>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('first-setup.store') }}">
            @csrf

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nom affiché <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', auth()->user()->name) }}" required
                           class="block w-full rounded-md border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900" />
                    <p class="text-xs text-slate-400 mt-0.5">Comment votre nom apparaîtra sur le site.</p>
                </div>

                <div>
                    <label for="pseudonyme" class="block text-sm font-medium text-slate-700 mb-1">Pseudo</label>
                    <input type="text" name="pseudonyme" id="pseudonyme" value="{{ old('pseudonyme', auth()->user()->pseudonyme) }}"
                           class="block w-full rounded-md border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900" />
                </div>
            </div>

            <div class="mt-4">
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Adresse e-mail <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email', auth()->user()->email) }}" required
                       class="block w-full rounded-md border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900" />
                <p class="text-xs text-slate-400 mt-0.5">L'e-mail sert de secours pour la connexion et la récupération. Jamais partagée.</p>
            </div>

            <div class="mt-4">
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Nouveau mot de passe <span class="text-red-500">*</span></label>
                <input type="password" name="password" id="password" required
                       class="block w-full rounded-md border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900" />
                <p class="text-xs text-slate-400 mt-0.5">Minimum 8 caractères.</p>
            </div>

            <div class="mt-4">
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirmer le mot de passe <span class="text-red-500">*</span></label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                       class="block w-full rounded-md border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900" />
            </div>

            <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="text-sm text-slate-500 hover:text-red-600 transition">
                    Annuler et me deconnecter
                </a>

                <button type="submit" class="px-5 py-2.5 bg-emerald-700 text-white text-sm font-medium rounded-md hover:bg-emerald-800 transition">
                    Enregistrer mon profil
                </button>
            </div>
        </form>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
    </div>
</div>
@endsection
