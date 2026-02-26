<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // ICED COFFEE Items - Only the 7 official items with images
            ['name' => 'LALATTE', 'price' => 39, 'category' => 'iced_coffee', 'size' => '12oz', 'image' => 'products/iced-coffee/LALATTE.png'],
            ['name' => 'LALATTE', 'price' => 49, 'category' => 'iced_coffee', 'size' => '16oz', 'image' => 'products/iced-coffee/LALATTE.png'],
            ['name' => 'LALATTE', 'price' => 59, 'category' => 'iced_coffee', 'size' => '22oz', 'image' => 'products/iced-coffee/LALATTE.png'],
            ['name' => 'MACHAKIT', 'price' => 39, 'category' => 'iced_coffee', 'size' => '12oz', 'image' => 'products/iced-coffee/MACHAKIT.png'],
            ['name' => 'MACHAKIT', 'price' => 49, 'category' => 'iced_coffee', 'size' => '16oz', 'image' => 'products/iced-coffee/MACHAKIT.png'],
            ['name' => 'MACHAKIT', 'price' => 59, 'category' => 'iced_coffee', 'size' => '22oz', 'image' => 'products/iced-coffee/MACHAKIT.png'],
            ['name' => 'VAGINILLA', 'price' => 39, 'category' => 'iced_coffee', 'size' => '12oz', 'image' => 'products/iced-coffee/VAGINILLA.png'],
            ['name' => 'VAGINILLA', 'price' => 49, 'category' => 'iced_coffee', 'size' => '16oz', 'image' => 'products/iced-coffee/VAGINILLA.png'],
            ['name' => 'VAGINILLA', 'price' => 59, 'category' => 'iced_coffee', 'size' => '22oz', 'image' => 'products/iced-coffee/VAGINILLA.png'],
            ['name' => 'CAPPUKINO', 'price' => 39, 'category' => 'iced_coffee', 'size' => '12oz', 'image' => 'products/iced-coffee/CAPPUKINO.png'],
            ['name' => 'CAPPUKINO', 'price' => 49, 'category' => 'iced_coffee', 'size' => '16oz', 'image' => 'products/iced-coffee/CAPPUKINO.png'],
            ['name' => 'CAPPUKINO', 'price' => 59, 'category' => 'iced_coffee', 'size' => '22oz', 'image' => 'products/iced-coffee/CAPPUKINO.png'],
            ['name' => 'MUKAPEPE', 'price' => 39, 'category' => 'iced_coffee', 'size' => '12oz', 'image' => 'products/iced-coffee/MUKAPEPE.png'],
            ['name' => 'MUKAPEPE', 'price' => 49, 'category' => 'iced_coffee', 'size' => '16oz', 'image' => 'products/iced-coffee/MUKAPEPE.png'],
            ['name' => 'MUKAPEPE', 'price' => 59, 'category' => 'iced_coffee', 'size' => '22oz', 'image' => 'products/iced-coffee/MUKAPEPE.png'],
            ['name' => 'TSOKOLATTI', 'price' => 39, 'category' => 'iced_coffee', 'size' => '12oz', 'image' => 'products/iced-coffee/TSOKOLATTI.png'],
            ['name' => 'TSOKOLATTI', 'price' => 49, 'category' => 'iced_coffee', 'size' => '16oz', 'image' => 'products/iced-coffee/TSOKOLATTI.png'],
            ['name' => 'TSOKOLATTI', 'price' => 59, 'category' => 'iced_coffee', 'size' => '22oz', 'image' => 'products/iced-coffee/TSOKOLATTI.png'],
            ['name' => 'KARAMANIAK', 'price' => 39, 'category' => 'iced_coffee', 'size' => '12oz', 'image' => 'products/iced-coffee/KARAMANAC.png'],
            ['name' => 'KARAMANIAK', 'price' => 49, 'category' => 'iced_coffee', 'size' => '16oz', 'image' => 'products/iced-coffee/KARAMANAC.png'],
            ['name' => 'KARAMANIAK', 'price' => 59, 'category' => 'iced_coffee', 'size' => '22oz', 'image' => 'products/iced-coffee/KARAMANAC.png'],

            // MILK TEA Items - Only the 12 specified flavors (no images)
            ['name' => 'Dark Chocolate', 'price' => 59, 'category' => 'milk_tea', 'size' => '16oz', 'image' => null],
            ['name' => 'Dark Chocolate', 'price' => 79, 'category' => 'milk_tea', 'size' => '22oz', 'image' => null],
            ['name' => 'Chocolate', 'price' => 59, 'category' => 'milk_tea', 'size' => '16oz', 'image' => null],
            ['name' => 'Chocolate', 'price' => 79, 'category' => 'milk_tea', 'size' => '22oz', 'image' => null],
            ['name' => 'Matcha', 'price' => 59, 'category' => 'milk_tea', 'size' => '16oz', 'image' => null],
            ['name' => 'Matcha', 'price' => 79, 'category' => 'milk_tea', 'size' => '22oz', 'image' => null],
            ['name' => 'Taro', 'price' => 59, 'category' => 'milk_tea', 'size' => '16oz', 'image' => null],
            ['name' => 'Taro', 'price' => 79, 'category' => 'milk_tea', 'size' => '22oz', 'image' => null],
            ['name' => 'Okinawa', 'price' => 59, 'category' => 'milk_tea', 'size' => '16oz', 'image' => null],
            ['name' => 'Okinawa', 'price' => 79, 'category' => 'milk_tea', 'size' => '22oz', 'image' => null],
            ['name' => 'Caramel Sugar', 'price' => 59, 'category' => 'milk_tea', 'size' => '16oz', 'image' => null],
            ['name' => 'Caramel Sugar', 'price' => 79, 'category' => 'milk_tea', 'size' => '22oz', 'image' => null],
            ['name' => 'Cookies & Cream', 'price' => 59, 'category' => 'milk_tea', 'size' => '16oz', 'image' => null],
            ['name' => 'Cookies & Cream', 'price' => 79, 'category' => 'milk_tea', 'size' => '22oz', 'image' => null],
            ['name' => 'Red Velvet', 'price' => 59, 'category' => 'milk_tea', 'size' => '16oz', 'image' => null],
            ['name' => 'Red Velvet', 'price' => 79, 'category' => 'milk_tea', 'size' => '22oz', 'image' => null],
            ['name' => 'Hokaido', 'price' => 59, 'category' => 'milk_tea', 'size' => '16oz', 'image' => null],
            ['name' => 'Hokaido', 'price' => 79, 'category' => 'milk_tea', 'size' => '22oz', 'image' => null],
            ['name' => 'Hazelnut', 'price' => 59, 'category' => 'milk_tea', 'size' => '16oz', 'image' => null],
            ['name' => 'Hazelnut', 'price' => 79, 'category' => 'milk_tea', 'size' => '22oz', 'image' => null],
            ['name' => 'Wintermelon', 'price' => 59, 'category' => 'milk_tea', 'size' => '16oz', 'image' => null],
            ['name' => 'Wintermelon', 'price' => 79, 'category' => 'milk_tea', 'size' => '22oz', 'image' => null],
            ['name' => 'White Bunny', 'price' => 59, 'category' => 'milk_tea', 'size' => '16oz', 'image' => null],
            ['name' => 'White Bunny', 'price' => 79, 'category' => 'milk_tea', 'size' => '22oz', 'image' => null],

            // Sample items for other categories (unchanged)
            ['name' => 'Strawberry Milky Fruit Jam', 'price' => 140, 'category' => 'milky_fruit_jam', 'size' => null, 'image' => null],
            ['name' => 'Mango Milky Fruit Jam', 'price' => 145, 'category' => 'milky_fruit_jam', 'size' => null, 'image' => null],
            ['name' => 'Blueberry Milky Fruit Jam', 'price' => 140, 'category' => 'milky_fruit_jam', 'size' => null, 'image' => null],
            ['name' => 'Mixed Berry Milky Fruit Jam', 'price' => 150, 'category' => 'milky_fruit_jam', 'size' => null, 'image' => null],

            ['name' => 'Original Sticky Milk', 'price' => 110, 'category' => 'sticky_milk', 'size' => null, 'image' => null],
            ['name' => 'Cheese Sticky Milk', 'price' => 125, 'category' => 'sticky_milk', 'size' => null, 'image' => null],
            ['name' => 'Chocolate Sticky Milk', 'price' => 120, 'category' => 'sticky_milk', 'size' => null, 'image' => null],
            ['name' => 'Caramel Sticky Milk', 'price' => 130, 'category' => 'sticky_milk', 'size' => null, 'image' => null],

            ['name' => 'Lemon Soda', 'price' => 85, 'category' => 'fruit_soda', 'size' => null, 'image' => null],
            ['name' => 'Orange Soda', 'price' => 85, 'category' => 'fruit_soda', 'size' => null, 'image' => null],
            ['name' => 'Strawberry Soda', 'price' => 90, 'category' => 'fruit_soda', 'size' => null, 'image' => null],
            ['name' => 'Apple Soda', 'price' => 85, 'category' => 'fruit_soda', 'size' => null, 'image' => null],
            ['name' => 'Grape Soda', 'price' => 90, 'category' => 'fruit_soda', 'size' => null, 'image' => null],

            ['name' => 'Original Egg Waffle', 'price' => 75, 'category' => 'egg_waffle', 'size' => null, 'image' => null],
            ['name' => 'Chocolate Egg Waffle', 'price' => 85, 'category' => 'egg_waffle', 'size' => null, 'image' => null],
            ['name' => 'Strawberry Egg Waffle', 'price' => 90, 'category' => 'egg_waffle', 'size' => null, 'image' => null],
            ['name' => 'Matcha Egg Waffle', 'price' => 95, 'category' => 'egg_waffle', 'size' => null, 'image' => null],
        ];

        foreach ($items as $item) {
            Product::query()->updateOrCreate(
                ['name' => $item['name'], 'size' => $item['size']],
                [
                    'price' => $item['price'],
                    'category' => $item['category'],
                    'size' => $item['size'],
                    'image' => $item['image'],
                    'is_active' => true,
                ]
            );
        }
    }
}
