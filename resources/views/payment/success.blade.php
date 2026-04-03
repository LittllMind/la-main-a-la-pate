@extends('layouts.app')

@section('title', 'Paiement réussi')

@section('content')
<div class="max-w-2xl mx-auto text-center py-12">
    <div class="text-6xl mb-6">🎉</div>
    <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent mb-4">
        Paiement réussi !
    </h1>
    <p class="text-gray-300 text-lg mb-8">
        Merci pour votre commande. Votre paiement a été confirmé.
    </p>

    <div class="bg-gray-800 rounded-2xl p-6 mb-8">
        <div class="grid grid-cols-2 gap-4 text-left">
            <div>
                <span class="text-gray-400">Numéro de commande</span>
                <p class="text-white font-semibold">#{{ $payment->order->id }}</p>
            </div>
            <div>
                <span class="text-gray-400">Montant payé</span>
                <p class="text-white font-semibold">{{ number_format($payment->amount, 2, ',', ' ') }} €</p>
            </div>
            <div>
                <span class="text-gray-400">Date</span>
                <p class="text-white font-semibold">{{ $payment->paid_at->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <span class="text-gray-400">Statut</span>
                <p class="text-green-400 font-semibold">Payé</p>
            </div>
        </div>
    </div>

    <a href="{{ route('kiosque.index') }}" 
        class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold py-3 px-8 rounded-xl transition">
        Retour au catalogue
    </a>
</div>
@endsection