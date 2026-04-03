<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Database\Seeders\UserSeeder;
use Database\Seeders\BougieSeeder;
use Database\Seeders\VenteSeeder;
use Database\Seeders\FondSeeder;
use Database\Seeders\MouvementStockSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        $this->call([
            UserSeeder::class,
            FondSeeder::class,
            VinyleSeeder::class,
            BougieSeeder::class,
            MouvementStockSeeder::class,
            VenteSeeder::class,
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}