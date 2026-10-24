<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $categoryMap = Category::query()->pluck('id', 'code');

        $menus = [
            // KAT-01 Kopi Espresso Based
            ['code' => 'MNU-001', 'category' => 'KAT-01', 'name' => 'Espresso', 'price' => 18000, 'signature' => false, 'image' => 'espresso.jpg'],
            ['code' => 'MNU-002', 'category' => 'KAT-01', 'name' => 'Americano', 'price' => 22000, 'signature' => false, 'image' => 'americano.jpg'],
            ['code' => 'MNU-003', 'category' => 'KAT-01', 'name' => 'Kopi Susu Gula Aren', 'price' => 24000, 'signature' => true, 'image' => 'kopi-susu-gula-aren.jpg'],
            ['code' => 'MNU-004', 'category' => 'KAT-01', 'name' => 'Cappuccino', 'price' => 28000, 'signature' => false, 'image' => 'cappuccino.jpg'],
            ['code' => 'MNU-005', 'category' => 'KAT-01', 'name' => 'Caffe Latte', 'price' => 28000, 'signature' => false, 'image' => 'caffe-latte.jpg'],
            ['code' => 'MNU-006', 'category' => 'KAT-01', 'name' => 'Mocha', 'price' => 32000, 'signature' => false, 'image' => 'mocha.jpg'],
            ['code' => 'MNU-007', 'category' => 'KAT-01', 'name' => 'Caramel Macchiato', 'price' => 32000, 'signature' => false, 'image' => 'caramel-macchiato.jpg'],
            ['code' => 'MNU-008', 'category' => 'KAT-01', 'name' => 'Affogato', 'price' => 30000, 'signature' => false, 'image' => 'affogato.jpg'],
            // KAT-02 Kopi Manual Brew
            ['code' => 'MNU-009', 'category' => 'KAT-02', 'name' => 'V60 Single Origin', 'price' => 35000, 'signature' => true, 'image' => 'v60-single-origin.jpg'],
            ['code' => 'MNU-010', 'category' => 'KAT-02', 'name' => 'Japanese Iced Coffee', 'price' => 33000, 'signature' => false, 'image' => 'japanese-iced-coffee.jpg'],
            ['code' => 'MNU-011', 'category' => 'KAT-02', 'name' => 'Cold Brew', 'price' => 30000, 'signature' => false, 'image' => 'cold-brew.jpg'],
            // KAT-03 Non-Kopi
            ['code' => 'MNU-012', 'category' => 'KAT-03', 'name' => 'Matcha Latte', 'price' => 30000, 'signature' => false, 'image' => 'matcha-latte.jpg'],
            ['code' => 'MNU-013', 'category' => 'KAT-03', 'name' => 'Chocolate', 'price' => 26000, 'signature' => false, 'image' => 'chocolate.jpg'],
            ['code' => 'MNU-014', 'category' => 'KAT-03', 'name' => 'Red Velvet Latte', 'price' => 28000, 'signature' => false, 'image' => 'red-velvet-latte.jpg'],
            ['code' => 'MNU-015', 'category' => 'KAT-03', 'name' => 'Lemon Tea', 'price' => 20000, 'signature' => false, 'image' => 'lemon-tea.jpg'],
            ['code' => 'MNU-016', 'category' => 'KAT-03', 'name' => 'Lychee Tea', 'price' => 22000, 'signature' => false, 'image' => 'lychee-tea.jpg'],
            ['code' => 'MNU-017', 'category' => 'KAT-03', 'name' => 'Air Mineral', 'price' => 8000, 'signature' => false, 'image' => 'air-mineral.jpg'],
            // KAT-04 Makanan
            ['code' => 'MNU-018', 'category' => 'KAT-04', 'name' => 'Nasi Goreng Magezz', 'price' => 28000, 'signature' => true, 'image' => 'nasi-goreng-magezz.jpg'],
            ['code' => 'MNU-019', 'category' => 'KAT-04', 'name' => 'Mie Goreng Spesial', 'price' => 26000, 'signature' => false, 'image' => 'mie-goreng-spesial.jpg'],
            ['code' => 'MNU-020', 'category' => 'KAT-04', 'name' => 'Chicken Rice Bowl', 'price' => 30000, 'signature' => false, 'image' => 'chicken-rice-bowl.jpg'],
            ['code' => 'MNU-021', 'category' => 'KAT-04', 'name' => 'Beef Rice Bowl', 'price' => 35000, 'signature' => false, 'image' => 'beef-rice-bowl.jpg'],
            ['code' => 'MNU-022', 'category' => 'KAT-04', 'name' => 'Club Sandwich', 'price' => 25000, 'signature' => false, 'image' => 'club-sandwich.jpg'],
            ['code' => 'MNU-023', 'category' => 'KAT-04', 'name' => 'Butter Croissant', 'price' => 22000, 'signature' => false, 'image' => 'butter-croissant.jpg'],
            // KAT-05 Snack & Pastry
            ['code' => 'MNU-024', 'category' => 'KAT-05', 'name' => 'French Fries', 'price' => 20000, 'signature' => false, 'image' => 'french-fries.jpg'],
            ['code' => 'MNU-025', 'category' => 'KAT-05', 'name' => 'Pisang Goreng Keju', 'price' => 18000, 'signature' => false, 'image' => 'pisang-goreng-keju.jpg'],
            ['code' => 'MNU-026', 'category' => 'KAT-05', 'name' => 'Donat Gula', 'price' => 15000, 'signature' => false, 'image' => 'donat-gula.jpg'],
            ['code' => 'MNU-027', 'category' => 'KAT-05', 'name' => 'Fudgy Brownies', 'price' => 18000, 'signature' => false, 'image' => 'fudgy-brownies.jpg'],
            ['code' => 'MNU-028', 'category' => 'KAT-05', 'name' => 'Cheese Cake Slice', 'price' => 25000, 'signature' => false, 'image' => 'cheese-cake-slice.jpg'],
        ];

        foreach ($menus as $menu) {
            Menu::updateOrCreate(
                ['code' => $menu['code']],
                [
                    'category_id' => $categoryMap[$menu['category']],
                    'name' => $menu['name'],
                    'description' => null,
                    'image_path' => $menu['image'],
                    'price' => $menu['price'],
                    'is_signature' => $menu['signature'],
                    'is_active' => true,
                ]
            );
        }
    }
}
