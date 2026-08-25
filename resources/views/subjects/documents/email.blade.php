@extends('layouts.public')

@section('title', ($emailSubject ?: $document->title) . ' — La Main à la Pâte')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <a href="{{ route('subjects.show', $subject->slug) }}#documents" class="text-sm text-slate-500 hover:text-slate-900 mb-4 inline-block">← Retour au dossier</a>

    <div class="bg-white rounded-lg border border-slate-200 p-6">
        <h1 class="text-xl font-bold text-slate-900 mb-4">{{ $emailSubject ?: $document->title }}</h1>

        <dl class="grid grid-cols-1 sm:grid-cols-[8rem_1fr] gap-x-4 gap-y-2 text-sm mb-6">
            @if($emailDate)
                <dt class="text-slate-500">Date</dt>
                <dd class="text-slate-900">{{ $emailDate }}</dd>
            @endif
            @if($emailFrom)
                <dt class="text-slate-500">De</dt>
                <dd class="text-slate-900">{{ $emailFrom }}</dd>
            @endif
            @if($emailTo)
                <dt class="text-slate-500">À</dt>
                <dd class="text-slate-900">{{ $emailTo }}</dd>
            @endif
        </dl>

        @if($emailBody)
            <div class="prose prose-slate max-w-none text-slate-800 whitespace-pre-wrap">{{ $emailBody }}</div>
        @else
            <p class="text-slate-500 italic">Le corps du message n'est pas disponible dans cette représentation.</p>
        @endif
    </div>
</div>
@endsection
