@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-5xl font-bold text-center mb-12">
            <span class="bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                Contact
            </span>
        </h1>

        <div class="grid md:grid-cols-2 gap-12">
            <div class="space-y-8">
                <section class="bg-gray-800 rounded-2xl p-8 border border-gray-700">
                    <h2 class="text-2xl font-bold mb-6 flex items-center">
                        <span class="text-2xl mr-3">📍</span>
                        Localisation
                    </h2>
                    <p class="text-gray-300">
                        Le Rozier<br>
                        48150, France
                    </p>
                </section>

                <section class="bg-gray-800 rounded-2xl p-8 border border-gray-700">
                    <h2 class="text-2xl font-bold mb-6 flex items-center">
                        <span class="text-2xl mr-3">📧</span>
                        Email
                    </h2>
                    <p class="text-gray-300">
                        contact@vinyle-hydrodecoupe.fr
                    </p>
                </section>

                <section class="bg-gray-800 rounded-2xl p-8 border border-gray-700">
                    <h2 class="text-2xl font-bold mb-6 flex items-center">
                        <span class="text-2xl mr-3">🕐</span>
                        Horaires
                    </h2>
                    <p class="text-gray-300">
                        Du lundi au samedi<br>
                        9h00 - 18h00
                    </p>
                </section>
            </div>

            <div class="bg-gray-800 rounded-2xl p-8 border border-gray-700">
                <h2 class="text-2xl font-bold mb-6">Envoyez-nous un message</h2>
                <form class="space-y-6">
                    <div>
                        <label class="block text-gray-300 mb-2">Nom</label>
                        <input type="text" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-purple-500 transition" placeholder="Votre nom">
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-2">Email</label>
                        <input type="email" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-purple-500 transition" placeholder="votre@email.com">
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-2">Message</label>
                        <textarea rows="5" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-purple-500 transition" placeholder="Votre message..."></textarea>
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 py-3 rounded-lg font-semibold transition transform hover:scale-105">
                        Envoyer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection