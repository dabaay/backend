<?php

namespace Database\Seeders;

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
            [
                'product_code' => 'MAC001',
                'name' => 'Macaroni Santa Lucia',
                'image_path' => 'products/qm7MYxDhFBW1LupVWaB0E5bA5UtbTBiUEO1qNEq8.jpg',
                'category' => 'Cunto',
                'cost_price' => 0.40,
                'selling_price' => 0.60,
                'current_stock' => 98,
                'unit' => 'kg',
            ],
            [
                'product_code' => 'SAL001',
                'name' => 'Saliid Cadale 5L',
                'category' => 'Cunto',
                'cost_price' => 6.50,
                'selling_price' => 8.50,
                'current_stock' => 41,
                'unit' => 'gacan',
            ],
            [
                'product_code' => 'SON001',
                'name' => 'Sonkor 50KG',
                'category' => 'Cunto',
                'cost_price' => 28.00,
                'selling_price' => 32.00,
                'current_stock' => 19,
                'unit' => 'bac',
            ],
            [
                'product_code' => 'BUR001',
                'name' => 'Bur Libaax 50KG',
                'category' => 'Cunto',
                'cost_price' => 24.50,
                'selling_price' => 28.00,
                'current_stock' => 14,
                'unit' => 'bac',
            ],
            [
                'product_code' => 'BAS001',
                'name' => 'Bariis Bunni 25KG',
                'category' => 'Cunto',
                'cost_price' => 18.00,
                'selling_price' => 22.00,
                'current_stock' => 27,
                'unit' => 'bac',
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['product_code' => $product['product_code']],
                $product
            );
        }
    }
}
