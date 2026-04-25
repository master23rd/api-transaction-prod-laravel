<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Database\Seeder;

class ProductOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $options = [
            // Size options
            ['name' => 'Small', 'price' => 0, 'type' => 'size'],
            ['name' => 'Medium', 'price' => 5000, 'type' => 'size'],
            ['name' => 'Large', 'price' => 10000, 'type' => 'size'],

            // Dairy options
            // ['name' => 'Regular Milk', 'price' => 0, 'type' => 'dairy'],
            // ['name' => 'Oat Milk', 'price' => 8000, 'type' => 'dairy'],
            // ['name' => 'Almond Milk', 'price' => 10000, 'type' => 'dairy'],
            // ['name' => 'Soy Milk', 'price' => 6000, 'type' => 'dairy'],

            // // Sweetness options
            // ['name' => 'Normal Sugar', 'price' => 0, 'type' => 'sweetness'],
            // ['name' => 'Less Sugar', 'price' => 0, 'type' => 'sweetness'],
            // ['name' => 'No Sugar', 'price' => 0, 'type' => 'sweetness'],

            // // Ice options
            // ['name' => 'Normal Ice', 'price' => 0, 'type' => 'ice'],
            // ['name' => 'Less Ice', 'price' => 0, 'type' => 'ice'],
            // ['name' => 'No Ice', 'price' => 0, 'type' => 'ice'],
        ];

        // Get all beverage products (not snack, merchandise)
        $beverageProducts = Product::whereHas('category', function ($query) {
            $query->whereNotIn('slug', ['snack', 'merchandise']);
        })->get();

        foreach ($beverageProducts as $product) {
            foreach ($options as $option) {
                ProductOption::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'name' => $option['name'],
                        'type' => $option['type'],
                    ],
                    [
                        'price' => $option['price'],
                    ]
                );
            }
        }
    }
}
