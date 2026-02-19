<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Espresso', 'price' => 80, 'category' => 'coffee'],
            ['name' => 'Americano', 'price' => 95, 'category' => 'coffee'],
            ['name' => 'Cappuccino', 'price' => 120, 'category' => 'coffee'],
            ['name' => 'Latte', 'price' => 125, 'category' => 'coffee'],
            ['name' => 'Mocha', 'price' => 135, 'category' => 'coffee'],
            ['name' => 'Caramel Macchiato', 'price' => 140, 'category' => 'coffee'],
            ['name' => 'Flat White', 'price' => 130, 'category' => 'coffee'],
            ['name' => 'Iced Coffee', 'price' => 110, 'category' => 'coffee'],

            ['name' => 'Hot Chocolate', 'price' => 115, 'category' => 'non-coffee'],
            ['name' => 'Matcha Latte', 'price' => 135, 'category' => 'non-coffee'],
            ['name' => 'Chai Latte', 'price' => 125, 'category' => 'non-coffee'],
            ['name' => 'Fruit Tea', 'price' => 95, 'category' => 'non-coffee'],
            ['name' => 'Milk Tea', 'price' => 100, 'category' => 'non-coffee'],
            ['name' => 'Fresh Juice', 'price' => 110, 'category' => 'non-coffee'],

            ['name' => 'Blueberry Muffin', 'price' => 75, 'category' => 'snacks'],
            ['name' => 'Chocolate Croissant', 'price' => 85, 'category' => 'snacks'],
            ['name' => 'Banana Bread', 'price' => 70, 'category' => 'snacks'],
            ['name' => 'Cookies (3pcs)', 'price' => 60, 'category' => 'snacks'],
            ['name' => 'Cheesecake Slice', 'price' => 95, 'category' => 'snacks'],
            ['name' => 'Sandwich', 'price' => 120, 'category' => 'snacks'],
        ];

        foreach ($items as $item) {
            Product::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'price' => $item['price'],
                    'category' => $item['category'],
                    'is_active' => true,
                ]
            );
        }
    }
}
