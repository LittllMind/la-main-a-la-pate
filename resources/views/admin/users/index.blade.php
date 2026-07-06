@extends('layouts.admin')

@section('title', 'Utilisateurs')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Utilisateurs</h1>
            <p class="text-slate-600 text-sm">Gestion des comptes, rôles, emails et mots de passe.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">
            + Nouvel utilisateur
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 text-emerald-800 rounded-md text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 text-red-800 rounded-md text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Pseudonyme</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Rôle</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Commune</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($users as $u)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ $u->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $u->pseudonyme }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $u->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @php
                                $roleClass = match($u->role) {
                                    'admin' => 'bg-purple-100 text-purple-800',
                                    'moderator' => 'bg-blue-100 text-blue-800',
                                    'citoyen' => 'bg-emerald-100 text-emerald-800',
                                    default => 'bg-slate-100 text-slate-800',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $roleClass }}">
                                {{ $roles[$u->role] ?? $u->role }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $u->commune ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.users.edit', $u) }}" class="text-emerald-600 hover:text-emerald-800">Modifier</a>
                                <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="inline" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-slate-500">Aucun utilisateur.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
