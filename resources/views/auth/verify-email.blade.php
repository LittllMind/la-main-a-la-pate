<x-guest-layout>
    <div class="mb-4 text-sm text-slate-600">
        {{ __('Merci de votre inscription ! Avant de commencer, veuillez verifier votre adresse email en cliquant sur le lien que nous venons de vous envoyer.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('Un nouveau lien de verification a ete envoye a votre adresse email.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <div>
                <x-primary-button>
                    {{ __('Renvoyer l\'email de verification') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-500 hover:text-slate-900 underline">
                {{ __('Deconnexion') }}
            </button>
        </form>
    </div>
</x-guest-layout>
