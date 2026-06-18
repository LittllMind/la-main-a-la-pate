@extends('layouts.admin')

@section('title', 'Routes de l\'application')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Routes de l'application</h1>
        <p class="text-slate-600 text-sm">Liste complete, classee par groupe. Les liens directs mennent a des pages fonctionnelles pour les routes sans parametres dynamiques.</p>
    </div>

    @foreach($routeGroups as $groupName => $routes)
        <div class="bg-white rounded-xl border border-slate-200 mb-6 overflow-hidden">
            <div class="bg-slate-50 px-6 py-3 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">{{ $groupName }}</h2>
                <span class="text-xs text-slate-500">{{ count($routes) }} route(s)</span>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($routes as $route)
                    <div class="px-6 py-3 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <div class="flex items-center gap-2">
                            @foreach($route['methods'] as $method)
                                <span class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded route-method-{{ $method }}">
                                    {{ $method }}
                                </span>
                            @endforeach
                            @if($route['link'])
                                <a href="{{ $route['link'] }}" class="text-emerald-600 hover:underline text-sm">{{ $route['uri'] }}</a>
                            @else
                                <span class="text-slate-500 text-sm">{{ $route['uri'] }}</span>
                            @endif
                        </div>
                        <div class="flex-1 text-xs text-slate-400 truncate sm:text-right">
                            {{ $route['name'] }}
                        </div>
                        <div class="text-xs text-slate-400">
                            @if(!empty($route['middleware']))
                                {{ implode(', ', $route['middleware']) }}
                            @else
                                public
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
@endsection
