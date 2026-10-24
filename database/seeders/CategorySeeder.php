<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'KAT-01', 'name' => 'Kopi Espresso Based', 'description' => 'Minuman kopi berbasis espresso'],
            ['code' => 'KAT-02', 'name' => 'Kopi Manual Brew', 'description' => 'Kopi seduh manual (V60, cold brew, dsb.)'],
            ['code' => 'KAT-03', 'name' => 'Non-Kopi', 'description' => 'Minuman tanpa kopi'],
            ['code' => 'KAT-04', 'name' => 'Makanan', 'description' => 'Menu makanan utama'],
            ['code' => 'KAT-05', 'name' => 'Snack & Pastry', 'description' => 'Camilan dan kue'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['code' => $category['code']],
                array_merge($category, ['is_active' => true])
            );
        }
    }
}
