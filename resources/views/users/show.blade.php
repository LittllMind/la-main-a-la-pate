<x-app-layout>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Profil utilisateur
        </h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-medium text-gray-900">Informations</h3>
                    <div class="mt-4 space-y-2">
                        <div>
                            <span class="text-gray-500">Nom:</span>
                            <span class="ml-2 font-medium">{{ $user->name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Email:</span>
                            <span class="ml-2">{{ $user->email }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Rôle:</span>
                            <span class="ml-2">{{ $user->getRoleLabel() }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Inscrit le:</span>
                            <span class="ml-2">{{ $user->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>

                    @if (auth()->id() === $user->id || auth()->user()->isAdmin())
                        <div class="mt-6">
                            <a href="{{ route('users.edit', $user) }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Modifier le profil
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
