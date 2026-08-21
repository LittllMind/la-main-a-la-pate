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

            {{-- Barre de recherche rapide --}}
            @auth
                <form action="{{ route('search') }}" method="GET" class="hidden md:flex items-center">
                    <div class="relative">
                        <input type="text" name="q" placeholder="Rechercher..." minlength="2"
                               class="w-32 lg:w-48 rounded-full border-slate-200 bg-slate-50 text-sm pl-8 pr-3 py-1.5 focus:border-emerald-500 focus:ring-emerald-500 focus:w-64 transition-all"
                               required>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>
                </form>
            @endauth

            {{-- Desktop secondary nav + hamburger trigger --}}
            <div class="flex items-center gap-2">
                @auth
                    @if(app()->environment('local') && auth()->user()->id === 1)
                        <div class="hidden lg:flex items-center mr-2" title="Mode local uniquement">
                            <div class="flex items-center gap-1 bg-indigo-50 border border-indigo-200 rounded-lg px-2 py-1">
                                <span class="text-[10px] uppercase font-semibold text-indigo-700 mr-1">Tester comme :</span>
                                <a href="{{ route('impersonate.become', 'member') }}?redirect={{ urlencode(url()->current()) }}" class="text-xs px-2 py-1 rounded bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-100 transition">Membre</a>
                                <a href="{{ route('impersonate.become', 'citoyen') }}?redirect={{ urlencode(url()->current()) }}" class="text-xs px-2 py-1 rounded bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-100 transition">Citoyen</a>
                                <a href="{{ route('impersonate.become', 'moderator') }}?redirect={{ urlencode(url()->current()) }}" class="text-xs px-2 py-1 rounded bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-100 transition">Modérateur</a>
                                <a href="{{ route('impersonate.become', 'admin') }}?redirect={{ urlencode(url()->current()) }}" class="text-xs px-2 py-1 rounded bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-100 transition">Admin test</a>
                                <form method="POST" action="{{ route('logout') }}" class="inline m-0">
                                    @csrf
                                    <input type="hidden" name="redirect" value="{{ url()->current() }}">
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-100 transition">Visiteur</button>
                                </form>
                            </div>
                        </div>
                    @endif

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

                    {{-- Desktop logout --}}
                    <div class="hidden lg:flex items-center gap-2">
                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-2 px-3 py-2 rounded-md text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">
                            <span class="inline-block w-6 h-6 rounded-full flex items-center justify-center text-white text-[10px] font-semibold"
                                  style="background-color: {{ auth()->user()->color ?: '#64748b' }}">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </span>
                            <div class="flex flex-col">
                                <span class="max-w-[8rem] truncate">{{ auth()->user()->name }}</span>
                                @if(session('impersonate_admin_id'))
                                    <span class="text-[10px] text-amber-600 font-semibold">Impersonation {{ auth()->user()->role }}</span>
                                @endif
                            </div>
                        </a>
                        @if(session('impersonate_admin_id'))
                            <form method="POST" action="{{ route('impersonate.restore') }}" class="inline" title="Revenir au compte admin principal">
                                @csrf
                                <input type="hidden" name="redirect" value="{{ url()->current() }}">
                                <button type="submit" class="flex items-center gap-1 px-3 py-2 rounded-md text-xs text-amber-700 bg-amber-50 border border-amber-200 hover:bg-amber-100 transition">
                                    Revenir admin
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="flex items-center gap-1 px-3 py-2 rounded-md text-sm text-red-600 hover:bg-red-50 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                </svg>
                                Deconnexion
                            </button>
                        </form>
                    </div>

                    {{-- Mobile hamburger --}}
                    <button id="mobile-menu-btn" class="p-2 rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900 lg:hidden" aria-label="Menu">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                @else
                    {{-- Guest : aucun lien vers catalogue, communauté, hall ou login. --}}
                    <span class="text-sm text-slate-400 px-3 py-2">La Main à la Pâte</span>
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
                        <div class="py-2 space-y-1 border-t border-slate-100">
                            <p class="px-2 py-1 text-xs font-semibold text-slate-400 uppercase tracking-wider">Administration</p>
                            <a href="{{ route('admin.panel') }}" class="flex items-center gap-3 px-2 py-2 text-sm rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm0 1.5a8.25 8.25 0 110 16.5 8.25 8.25 0 010-16.5zM12 10.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM12 12V3m0 9l6.75 4.5m-6.75-4.5l-6.75 4.5" />
                                </svg>
                                Tableau de bord
                            </a>
                            <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-2 py-2 text-sm rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.617z" />
                                </svg>
                                Utilisateurs
                            </a>
                            <a href="{{ route('admin.posts.index') }}" class="flex items-center gap-3 px-2 py-2 text-sm rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                Articles
                            </a>
                            <a href="{{ route('admin.sections.index') }}" class="flex items-center gap-3 px-2 py-2 text-sm rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                </svg>
                                Sections
                            </a>
                            <a href="{{ route('admin.routes') }}" class="flex items-center gap-3 px-2 py-2 text-sm rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0m-15 0H3m16.5 0H21m-1.5 0H3m16.5 0H21" />
                                </svg>
                                Routes
                            </a>
                        </div>
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
