<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Premium Wireless Headphones',
                'base_price' => 199.99,
                'description' => 'Noise-cancelling over-ear wireless headphones with premium sound quality.'
            ],
            [
                'name' => 'Ultra Slim Smartphone',
                'base_price' => 899.99,
                'description' => 'Flagship smartphone with 120Hz display and high-end camera setup.'
            ],
            [
                'name' => 'Portable Gaming Console',
                'base_price' => 349.99,
                'description' => 'Handheld gaming console with 7-inch OLED screen.'
            ],
            [
                'name' => 'Ergonomic Mechanical Keyboard',
                'base_price' => 129.50,
                'description' => 'Mechanical keyboard with hot-swappable switches and RGB lighting.'
            ],
            [
                'name' => 'Smart Fitness Watch',
                'base_price' => 249.00,
                'description' => 'Waterproof fitness watch with heart rate and GPS tracking.'
            ],
            [
                'name' => '4K Ultra-Wide Monitor',
                'base_price' => 599.99,
                'description' => '34-inch curved ultra-wide monitor for professional work and gaming.'
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['name' => $product['name']], $product);
        }
    }
}
