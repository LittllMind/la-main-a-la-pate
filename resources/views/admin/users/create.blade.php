@extends('layouts.admin')

@section('title', 'Créer un utilisateur')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Créer un utilisateur</h1>
        <p class="text-slate-600 text-sm">La création de compte est réservée à l'administrateur.</p>
    </div>

    @include('admin.users.form')
@endsection
