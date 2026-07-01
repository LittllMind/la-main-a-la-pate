<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LandingSection;

class LandingSectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            [
                'key' => 'actualites',
                'title' => 'Hall du Rozier',
                'subtitle' => 'Les dernières publications de la commune.',
                'body' => null,
                'position' => 1,
                'is_active' => true,
            ],
            [
                'key' => 'seraphotheque',
                'title' => 'La Séraphothèque',
                'subtitle' => 'Commerce de proximité',
                'body' => '<div class="grid grid-cols-1 md:grid-cols-2">
                    <div class="h-56 md:h-auto overflow-hidden">
                        <img src="/images/seraphotheque/devanture.jpg" alt="Devanture de la Séraphothèque" class="w-full h-full object-cover hover:scale-105 transition duration-500">
                    </div>
                    <div class="p-6 md:p-8 flex flex-col justify-center">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-2xl">🏠</span>
                            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">Commerce de proximité</span>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-900 mb-2">La Séraphothèque</h2>
                        <p class="text-slate-600 text-sm leading-relaxed mb-4">Friperie, brocante et recyclerie au cœur du Rozier depuis 2022. Un espace de rencontre, une initiative locale, sociale et écologique.</p>
                        <div class="text-xs text-slate-500 mb-5">2 rue Louis Armand — 48150 Le Rozier — Ouvert à l'année</div>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route(\'seraphotheque\') }}" class="inline-block bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition">Découvrir la boutique</a>
                            <a href="https://www.change.org/p/pour-le-maintien-de-la-seraphotheque-au-cœur-du-rozier-48150" target="_blank" class="inline-block bg-white text-emerald-700 border border-emerald-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-50 transition">Signer la pétition</a>
                        </div>
                    </div>
                </div>',
                'position' => 2,
                'is_active' => true,
            ],
            [
                'key' => 'espace-membres',
                'title' => 'Espace membres',
                'subtitle' => 'Rejoignez les forums de discussion pour échanger avec vos voisins.',
                'body' => null,
                'position' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($sections as $section) {
            LandingSection::firstOrCreate(['key' => $section['key']], $section);
        }
    }
}
