<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            // Popcorn
            [
                'name' => 'Popcorn',
                'category' => 'snack',
                'price' => 3.00,
                'size' => 'petit',
                'stock' => 100,
                'is_available' => true,
                'image' => '🍿'
            ],
            [
                'name' => 'Popcorn',
                'category' => 'snack',
                'price' => 5.00,
                'size' => 'grand',
                'stock' => 80,
                'is_available' => true,
                'image' => '🍿'
            ],

            // Boissons
            [
                'name' => 'Coca-Cola',
                'category' => 'drink',
                'price' => 5.00,
                'size' => 'petit',
                'stock' => 50,
                'is_available' => true,
                'image' => '🥤'
            ],
            [
                'name' => 'Coca-Cola',
                'category' => 'drink',
                'price' => 7.00,
                'size' => 'grand',
                'stock' => 50,
                'is_available' => true,
                'image' => '🥤'
            ],
            [
                'name' => 'Sprite',
                'category' => 'drink',
                'price' => 5.00,
                'size' => 'petit',
                'stock' => 50,
                'is_available' => true,
                'image' => '🥤'
            ],
            [
                'name' => 'Fanta',
                'category' => 'drink',
                'price' => 5.00,
                'size' => 'petit',
                'stock' => 50,
                'is_available' => true,
                'image' => '🥤'
            ],
            [
                'name' => 'Eau minérale',
                'category' => 'drink',
                'price' => 3.00,
                'size' => null,
                'stock' => 100,
                'is_available' => true,
                'image' => '💧'
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                [
                    'name' => $product['name'],
                    'size' => $product['size']
                ],
                $product
            );
        }

        $this->command->info('✅ Produits créés avec succès!');
    }
}
