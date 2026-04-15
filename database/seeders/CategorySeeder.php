<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Lokal', 'icon' => '🇮🇩', 'order' => 1],
            ['name' => 'Barat', 'icon' => '🇺🇸', 'order' => 2],
            ['name' => 'Japan', 'icon' => '🇯🇵', 'order' => 3],
            ['name' => 'Korea', 'icon' => '🇰🇷', 'order' => 4],
            ['name' => 'Cina', 'icon' => '🇨🇳', 'order' => 5],
            ['name' => 'Lainnya', 'icon' => '📁', 'order' => 10],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                [
                    'icon' => $category['icon'],
                    'order' => $category['order'],
                ]
            );
        }
    }
}
