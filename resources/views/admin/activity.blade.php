@extends('layouts.admin')

@section('title', 'Journal d\'activité')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Journal d'activité</h1>
        <p class="text-slate-600 text-sm">Suivi des connexions et modifications sur la plateforme.</p>
    </div>

    {{-- Stats rapides --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach([
            ['label' => 'Total logs', 'value' => $stats['total']],
            ['label' => 'Connexions aujourd\'hui', 'value' => $stats['logins_today']],
            ['label' => 'Créations aujourd\'hui', 'value' => $stats['creations_today']],
            ['label' => 'Modifs aujourd\'hui', 'value' => $stats['updates_today']],
        ] as $stat)
            <div class="bg-white rounded-lg border border-slate-200 p-4">
                <div class="text-2xl font-bold text-emerald-600">{{ $stat['value'] }}</div>
                <div class="text-xs text-slate-500">{{ $stat['label'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('admin.activity') }}" class="bg-white rounded-lg border border-slate-200 p-4 mb-6">
        <div class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..."
                    class="w-full rounded-md border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
            <select name="event_type" class="rounded-md border-slate-300 text-sm focus:border-emerald-500">
                <option value="">Tous les événements</option>
                @foreach($eventTypes as $type)
                    <option value="{{ $type }}" {{ request('event_type') == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
            <select name="entity_type" class="rounded-md border-slate-300 text-sm focus:border-emerald-500">
                <option value="">Toutes les entités</option>
                @foreach($entityTypes as $type)
                    <option value="{{ $type }}" {{ request('entity_type') == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm hover:bg-emerald-700">Filtrer</button>
            @if(request()->hasAny(['search','event_type','entity_type']))
                <a href="{{ route('admin.activity') }}" class="px-4 py-2 text-slate-600 text-sm hover:text-slate-900">Reset</a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Utilisateur</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Événement</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Description</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Entité</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($logs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm text-slate-500 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-slate-900">
                            <div class="font-medium">{{ $log->user?->name ?? 'Anonyme' }}</div>
                            @if($log->user?->pseudonyme)
                                <div class="text-xs text-slate-500">{{ $log->user->pseudonyme }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @switch($log->event_type)
                                @case('login') @php $badgeClass = 'bg-emerald-100 text-emerald-800'; @endphp @break
                                @case('logout') @php $badgeClass = 'bg-slate-100 text-slate-800'; @endphp @break
                                @case('create') @php $badgeClass = 'bg-blue-100 text-blue-800'; @endphp @break
                                @case('update') @php $badgeClass = 'bg-amber-100 text-amber-800'; @endphp @break
                                @case('delete') @php $badgeClass = 'bg-red-100 text-red-800'; @endphp @break
                                @case('publish') @php $badgeClass = 'bg-purple-100 text-purple-800'; @endphp @break
                                @default @php $badgeClass = 'bg-gray-100 text-gray-800'; @endphp @break
                            @endswitch
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeClass }}">
                                {{ ucfirst($log->event_type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $log->description }}</td>
                        <td class="px-4 py-3 text-sm text-slate-500">{{ $log->entity_type ? ucfirst($log->entity_type) . ' #' . $log->entity_id : '-' }}</td>
                        <td class="px-4 py-3 text-xs text-slate-400 font-mono">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucun log pour le moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
