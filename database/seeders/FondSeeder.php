<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Fond;

class FondSeeder extends Seeder
{
    public function run(): void
    {
        // Standard (pas de fond spécial) - pas de supplément
        Fond::updateOrCreate(
            ['type' => 'standard'],
            [
                'quantite'   => 999,
                'prix_achat' => 0,
                'prix_vente' => 0,
            ]
        );

        // Miroir - supplément de 8€
        Fond::updateOrCreate(
            ['type' => 'miroir'],
            [
                'quantite'   => 100,
                'prix_achat' => 8,
                'prix_vente' => 8,
            ]
        );

        // Doré - supplément de 13€
        Fond::updateOrCreate(
            ['type' => 'dore'],
            [
                'quantite'   => 100,
                'prix_achat' => 13,
                'prix_vente' => 13,
            ]
        );
    }
}
