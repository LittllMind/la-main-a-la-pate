@php
    $navItems = [
        ['route' => 'subjects.tree.index', 'label' => 'Arbre Sujets', 'icon' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M19.125 15.75H15M15 15.75l-1.5-1.5'],
        ['route' => 'documents.tree.documents', 'label' => 'Documents', 'icon' => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12.75A2.25 2.25 0 004.5 21h15a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 00-1.06.44z'],
        ['route' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm0 1.5a8.25 8.25 0 110 16.5 8.25 8.25 0 010-16.5zM12 10.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM12 12V3m0 9l6.75 4.5m-6.75-4.5l-6.75 4.5'],
    ];
@endphp

<nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="flex justify-between h-14 items-center">
            {{-- Brand --}}
            <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                <span class="hidden sm:inline">La Main a la Pate</span>
                <span class="sm:hidden">LMLaP</span>
            </a>

            {{-- Desktop main nav --}}
            <div class="hidden lg:flex items-center gap-1 text-sm">
                @auth
                    @foreach($navItems as $item)
                        @php $isActive = request()->routeIs($item['route'] . '*'); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-2 px-3 py-2 rounded-md transition {{ $isActive ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                            </svg>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                @endauth
            </div>

            {{-- Desktop secondary nav + hamburger trigger --}}
            <div class="flex items-center gap-2">
                @auth
                    {{-- Mobile icons --}}
                    <div class="flex lg:hidden items-center gap-1">
                        @foreach($navItems as $item)
                            @php $isActive = request()->routeIs($item['route'] . '*'); @endphp
                            <a href="{{ route($item['route']) }}"
                               class="p-2 rounded-md transition {{ $isActive ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                               aria-label="{{ $item['label'] }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                                </svg>
                            </a>
                        @endforeach
                    </div>

                    {{-- Mobile hamburger --}}
                    <button id="mobile-menu-btn" class="p-2 rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900 lg:hidden" aria-label="Menu">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                @else
                    <a href="{{ route('seraphotheque') }}" class="text-sm text-slate-600 hover:text-slate-900 px-3 py-2">Seraphotheque</a>
                    <a href="{{ route('login') }}" class="text-sm text-slate-600 hover:text-slate-900 px-3 py-2">Connexion</a>
                @endauth
            </div>
        </div>

        {{-- Mobile menu --}}
        <div id="mobile-menu" class="hidden lg:hidden border-t border-slate-100 pb-4">
            @auth
                <div class="py-3 border-b border-slate-100">
                    <div class="flex items-center gap-3 text-sm">
                        <span class="inline-block w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-semibold" style="background-color: {{ auth()->user()->color ?: '#64748b' }}">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </span>
                        <div>
                            <p class="font-medium text-slate-900">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500">{{ auth()->user()->role }}</p>
                        </div>
                    </div>
                </div>

                {{-- Les 3 onglets principaux sont déjà visibles hors hamburger ; on les retire du menu mobile --}}
                <div class="py-2 space-y-1">
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-2 py-2 text-sm rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.617z" />
                        </svg>
                        Mon profil
                    </a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.panel') }}" class="flex items-center gap-3 px-2 py-2 text-sm rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0m-15 0H3m16.5 0H21m-1.5 0H3m16.5 0H21" />
                            </svg>
                            Administration
                        </a>
                    @endif
                </div>

                <div class="border-t border-slate-100 pt-2 mt-2">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-2 py-2 text-sm rounded-md text-red-600 hover:bg-red-50 transition text-left">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                            Deconnexion
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </div>
</nav>

@auth
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        if (btn && menu) {
            btn.addEventListener('click', function () {
                menu.classList.toggle('hidden');
            });
        }
    });
</script>
@endauth
