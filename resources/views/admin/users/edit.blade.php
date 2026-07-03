@extends('layouts.admin')

@section('title', 'Modifier un utilisateur')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Modifier {{ $user->name }}</h1>
        <p class="text-slate-600 text-sm">L'email modifié redeviendra non vérifié jusqu'à confirmation.</p>
    </div>

    @include('admin.users.form')
@endsection
