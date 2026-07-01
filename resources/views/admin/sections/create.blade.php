@extends('layouts.admin')

@section('title', 'Nouvelle section du Hall')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Nouvelle section</h1>
        <p class="text-slate-600 text-sm">Créer une section affichée sur la page d'accueil.</p>
    </div>

    @include('admin.sections.form', ['section' => $section, 'route' => route('admin.sections.store'), 'method' => 'POST'])
@endsection
