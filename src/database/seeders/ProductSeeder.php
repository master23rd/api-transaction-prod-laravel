<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            // Coffee
            ['name' => 'Classic Espresso', 'category' => 'coffee', 'price' => 25000, 'about' => 'A bold and intense coffee shot made from premium Arabica beans.', 'service_time' => 5, 'is_featured' => true],
            ['name' => 'Vanilla Latte', 'category' => 'coffee', 'price' => 45000, 'about' => 'Smooth espresso with steamed milk and vanilla syrup.', 'service_time' => 7, 'is_featured' => true],
            ['name' => 'Caramel Macchiato', 'category' => 'coffee', 'price' => 48000, 'about' => 'Rich caramel flavor blended with espresso and creamy milk.', 'service_time' => 7, 'is_featured' => true],
            ['name' => 'Iced Americano', 'category' => 'coffee', 'price' => 35000, 'about' => 'Chilled espresso with cold water for a refreshing taste.', 'service_time' => 4, 'is_featured' => false],
            ['name' => 'Cappuccino', 'category' => 'coffee', 'price' => 42000, 'about' => 'Perfect balance of espresso, steamed milk, and foam.', 'service_time' => 6, 'is_featured' => true],

            // Non Coffee
            ['name' => 'Chocolate Frappe', 'category' => 'non-coffee', 'price' => 45000, 'about' => 'Blended chocolate with milk and whipped cream.', 'service_time' => 6, 'is_featured' => true],
            ['name' => 'Strawberry Smoothie', 'category' => 'non-coffee', 'price' => 40000, 'about' => 'Fresh strawberries blended with yogurt and honey.', 'service_time' => 5, 'is_featured' => false],
            ['name' => 'Mango Juice', 'category' => 'non-coffee', 'price' => 35000, 'about' => 'Fresh mango juice with no added sugar.', 'service_time' => 4, 'is_featured' => false],

            // Tea
            ['name' => 'Green Tea Latte', 'category' => 'tea', 'price' => 38000, 'about' => 'Premium matcha with steamed milk.', 'service_time' => 5, 'is_featured' => true],
            ['name' => 'Earl Grey', 'category' => 'tea', 'price' => 28000, 'about' => 'Classic black tea with bergamot flavor.', 'service_time' => 4, 'is_featured' => false],
            ['name' => 'Chamomile Tea', 'category' => 'tea', 'price' => 30000, 'about' => 'Relaxing herbal tea with honey.', 'service_time' => 4, 'is_featured' => false],

            // Snack
            ['name' => 'Croissant', 'category' => 'snack', 'price' => 28000, 'about' => 'Buttery, flaky French pastry baked fresh daily.', 'service_time' => 2, 'is_featured' => true],
            ['name' => 'Chicken Sandwich', 'category' => 'snack', 'price' => 55000, 'about' => 'Grilled chicken with fresh vegetables and special sauce.', 'service_time' => 10, 'is_featured' => false],
            ['name' => 'Banana Bread', 'category' => 'snack', 'price' => 32000, 'about' => 'Moist banana bread with walnuts.', 'service_time' => 2, 'is_featured' => false],

            // Merchandise
            ['name' => 'Awake Coffee Tumbler', 'category' => 'merchandise', 'price' => 150000, 'about' => 'Stainless steel tumbler with Awake Coffee logo.', 'service_time' => 1, 'is_featured' => false],
            ['name' => 'Coffee Bean 250g', 'category' => 'merchandise', 'price' => 120000, 'about' => 'Premium Arabica beans roasted in-house.', 'service_time' => 1, 'is_featured' => false],
        ];

        foreach ($products as $product) {
            $category = Category::where('slug', $product['category'])->first();

            if ($category) {
                Product::firstOrCreate(
                    ['slug' => Str::slug($product['name'])],
                    [
                        'name' => $product['name'],
                        'slug' => Str::slug($product['name']),
                        'price' => $product['price'],
                        'rate' => fake()->randomFloat(1, 3.5, 5.0),
                        'thumbnail' => null,
                        'category_id' => $category->id,
                        'about' => $product['about'],
                        'service_time' => $product['service_time'],
                        'is_featured' => $product['is_featured'],
                    ]
                );
            }
        }
    }
}
