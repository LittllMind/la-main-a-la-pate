<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Actualite', 'slug' => 'actualite', 'color' => '#3b82f6'],
            ['name' => 'Evenements', 'slug' => 'evenements', 'color' => '#8b5cf6'],
            ['name' => 'Patrimoine', 'slug' => 'patrimoine', 'color' => '#f59e0b'],
            ['name' => 'Environnement', 'slug' => 'environnement', 'color' => '#10b981'],
        ];
        foreach ($categories as $cat) {
            Category::create($cat);
        }
    }
}
