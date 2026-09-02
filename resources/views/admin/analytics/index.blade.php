@extends('layouts.app')

@section('title', 'Audience LMALP')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">Audience LMALP</h1>

    <div class="flex gap-2 mb-6">
        <a href="{{ route('admin.analytics', ['period' => 0]) }}" class="px-4 py-2 rounded-md {{ $period === 0 ? 'bg-slate-900 text-white' : 'bg-white border text-slate-700' }}">Aujourd'hui</a>
        <a href="{{ route('admin.analytics', ['period' => 7]) }}" class="px-4 py-2 rounded-md {{ $period === 7 ? 'bg-slate-900 text-white' : 'bg-white border text-slate-700' }}">7 jours</a>
        <a href="{{ route('admin.analytics', ['period' => 30]) }}" class="px-4 py-2 rounded-md {{ $period === 30 ? 'bg-slate-900 text-white' : 'bg-white border text-slate-700' }}">30 jours</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white border rounded-lg p-4">
            <div class="text-sm text-slate-500">Visiteurs journaliers uniques</div>
            <div class="text-2xl font-bold">{{ $uniqueVisitorsToday }}</div>
            @if ($period === 0)
                <div class="text-xs text-slate-400">unique aujourd'hui</div>
            @endif
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-sm text-slate-500">Visiteurs journaliers cumulés</div>
            <div class="text-2xl font-bold">{{ $cumulativeVisitors }}</div>
            <div class="text-xs text-slate-400">{{ $cumulativeVisitors }} visiteurs-journées</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-sm text-slate-500">Visites approximatives</div>
            <div class="text-2xl font-bold">{{ $visitsCount }}</div>
            <div class="text-xs text-slate-400">30 min d'inactivité</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-sm text-slate-500">Pages vues</div>
            <div class="text-2xl font-bold">{{ $pageViews }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white border rounded-lg p-4">
            <div class="text-sm text-slate-500 mb-2">Visites Séraphothèque</div>
            <div class="text-xl font-bold">{{ $dossierViews }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-sm text-slate-500 mb-2">Vues documents</div>
            <div class="text-xl font-bold">{{ $documentViews }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-sm text-slate-500 mb-2">Téléchargements</div>
            <div class="text-xl font-bold">{{ $documentDownloads }}</div>
        </div>
    </div>

    @if ($curveLabels->isNotEmpty())
    <div class="bg-white border rounded-lg p-4 mb-8">
        <h2 class="text-lg font-semibold mb-4">Courbe journalière</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="pb-2">Jour</th>
                        <th class="pb-2">Visiteurs uniques</th>
                        <th class="pb-2">Pages vues</th>
                        <th class="pb-2">Vues documents</th>
                        <th class="pb-2">Téléchargements</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($curveLabels as $index => $label)
                        <tr>
                            <td class="py-2">{{ \Illuminate\Support\Carbon::parse($label)->format('d/m') }}</td>
                            <td class="py-2">{{ $curveVisitors[$index] }}</td>
                            <td class="py-2">{{ $curvePageViews[$index] }}</td>
                            <td class="py-2">{{ $curveDocViews[$index] }}</td>
                            <td class="py-2">{{ $curveDocDownloads[$index] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @include('admin.analytics._table', ['title' => 'Top pages', 'rows' => $topPages, 'col' => 'path'])
        @include('admin.analytics._table', ['title' => 'Top documents par vue', 'rows' => $topDocumentsViews, 'col' => 'document_key'])
        @include('admin.analytics._table', ['title' => 'Top documents par téléchargement', 'rows' => $topDocumentsDownloads, 'col' => 'document_key'])
        @include('admin.analytics._table', ['title' => 'Referrers', 'rows' => $referrers, 'col' => 'referrer_host'])
        @include('admin.analytics._table', ['title' => 'Appareils', 'rows' => $devices, 'col' => 'label'])
        @include('admin.analytics._table', ['title' => 'Navigateurs', 'rows' => $browsers, 'col' => 'label'])
    </div>

    <div class="mt-8 bg-slate-50 border rounded-lg p-4 text-sm text-slate-600">
        <p class="font-semibold mb-1">Limites des métriques</p>
        <p>L'identifiant visiteur est renouvelé chaque jour. Une même personne revenant plusieurs jours peut donc être comptée plusieurs fois sur les périodes de 7 ou 30 jours. Les chiffres « Visiteurs journaliers cumulés » reflètent la somme des visiteurs uniques par jour, pas un décompte réel de personnes distinctes.</p>
        <p class="mt-2">Une visite est approximée : premier événement d'un visitor_key journalier = nouvelle visite ; nouvelle visite après 30 minutes d'inactivité. Les visites ne traversent pas minuit.</p>
    </div>
</div>
@endsection
