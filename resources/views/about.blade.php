@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-5xl font-bold text-center mb-12">
            <span class="bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                Le Concept
            </span>
        </h1>

        <div class="space-y-12">
            <section class="bg-gray-800 rounded-2xl p-8 border border-gray-700">
                <h2 class="text-3xl font-bold mb-6 flex items-center">
                    <span class="text-3xl mr-3">✨</span>
                    Notre savoir-faire
                </h2>
                <p class="text-gray-300 text-lg leading-relaxed">
                    Nous sélectionnons chaque vinyle pour son potentiel artistique et son histoire musicale.
                    Chaque pièce est unique et transformée avec soin.
                </p>
            </section>

            <section class="bg-gray-800 rounded-2xl p-8 border border-gray-700">
                <h2 class="text-3xl font-bold mb-6 flex items-center">
                    <span class="text-3xl mr-3">🎵</span>
                    Pourquoi le vinyle ?
                </h2>
                <p class="text-gray-300 text-lg leading-relaxed">
                    Le vinyle n'est pas seulement un support musical, c'est un objet chargé d'émotion et de nostalgie.
                    Nous donnons une seconde vie à ces disques,
                    créant des objets de décoration uniques qui préservent l'âme de la musique qu'ils contenaient.
                </p>
            </section>

            <section class="bg-gray-800 rounded-2xl p-8 border border-gray-700">
                <h2 class="text-3xl font-bold mb-6 flex items-center">
                    <span class="text-3xl mr-3">🛒</span>
                    Comment acheter ?
                </h2>
                <div class="space-y-4 text-gray-300">
                    <div class="flex items-start">
                        <span class="bg-purple-600 text-white rounded-full w-8 h-8 flex items-center justify-center mr-4 mt-1">1</span>
                        <div>
                            <h3 class="font-semibold mb-1">Explorez le catalogue</h3>
                            <p>Parcourez notre collection de vinyles uniques disponibles.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-purple-600 text-white rounded-full w-8 h-8 flex items-center justify-center mr-4 mt-1">2</span>
                        <div>
                            <h3 class="font-semibold mb-1">Créez votre compte</h3>
                            <p>Inscrivez-vous gratuitement pour pouvoir commander.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-purple-600 text-white rounded-full w-8 h-8 flex items-center justify-center mr-4 mt-1">3</span>
                        <div>
                            <h3 class="font-semibold mb-1">Commandez en ligne</h3>
                            <p>Ajoutez vos pièces favorites au panier et passez commande.</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="text-center mt-12">
            <a href="{{ route('kiosque.index') }}" class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 px-8 py-4 rounded-xl text-lg font-semibold transition transform hover:scale-105">
                Découvrir la Collection
            </a>
        </div>
    </div>
</div>
@endsection
