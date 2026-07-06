@extends('layouts.public')

@section('title', 'Mon profil')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Mon profil</h1>
        <p class="text-slate-600 text-sm">Gerez vos informations personnelles et votre mot de passe.</p>
    </div>

    <!-- Informations -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <div class="max-w-xl">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    <!-- Mot de passe -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <div class="max-w-xl">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    <!-- Suppression -->
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="max-w-xl">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</div>
@endsection
