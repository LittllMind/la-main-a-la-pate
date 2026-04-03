@extends('layouts.app')

@section('title', 'Commande annulée')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto text-center">
        <!-- Icône d'annulation -->
        <div class="mb-6">
            <svg class="w-24 h-24 mx-auto text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>

        <!-- Titre -->
        <h1 class="text-4xl font-bold mb-4 bg-gradient-to-r from-violet-600 to-pink-600 bg-clip-text text-transparent">
            Commande annulée
        </h1>

        <!-- Message -->
        <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">
            Votre commande a été annulée. Votre panier a été conservé, vous pouvez reprendre votre achat quand vous le souhaitez.
        </p>

        <!-- Informations -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-white">Que voulez-vous faire ?</h2>
            <div class="space-y-4 text-gray-600 dark:text-gray-300">
                <p>Votre panier est toujours actif avec tous vos articles.</p>
                <p>Vous pouvez :</p>
                <ul class="text-left space-y-2">
                    <li class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-violet-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Reprendre votre commande
                    </li>
                    <li class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-violet-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Modifier vos informations de livraison
                    </li>
                    <li class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-violet-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Continuer vos achats
                    </li>
                </ul>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('orders.payment') }}" 
               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-violet-600 to-pink-600 hover:from-violet-700 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg transform hover:scale-105 transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Reprendre le paiement
            </a>
            
            <a href="{{ route('kiosque.index') }}" 
               class="inline-flex items-center px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-semibold rounded-xl shadow transform hover:scale-105 transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 15H4L5 9z"></path>
                </svg>
                Continuer mes achats
            </a>
        </div>
    </div>
</div>
@endsection
