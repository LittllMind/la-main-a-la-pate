@extends('layouts.admin')

@section('title', 'Modifier la section du Hall')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Modifier la section</h1>
        <p class="text-slate-600 text-sm">{{ $section->title }}</p>
    </div>

    @include('admin.sections.form', ['section' => $section, 'route' => route('admin.sections.update', $section->id), 'method' => 'PUT'])
@endsection
