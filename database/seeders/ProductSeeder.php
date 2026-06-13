<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductCategory;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // Electronics - Phones
            [
                'name' => 'iPhone 15 Pro Max',
                'sku_prefix' => 'IPHONE',
                'category_id' => 2,
                'brand' => 'Apple',
                'base_unit' => 'Unit',
                'base_cost_price' => 950.00,
                'base_sale_price' => 1199.00,
                'base_wholesale_price' => 1050.00,
                'weight' => 0.221,
                'min_stock' => 10,
                'max_stock' => 100,
                'has_spec' => 1,
                'status' => 1,
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'sku_prefix' => 'GALAXY',
                'category_id' => 2,
                'brand' => 'Samsung',
                'base_unit' => 'Unit',
                'base_cost_price' => 850.00,
                'base_sale_price' => 1099.00,
                'base_wholesale_price' => 950.00,
                'weight' => 0.233,
                'min_stock' => 8,
                'max_stock' => 80,
                'has_spec' => 1,
                'status' => 1,
            ],
            [
                'name' => 'Google Pixel 8 Pro',
                'sku_prefix' => 'PIXEL',
                'category_id' => 2,
                'brand' => 'Google',
                'base_unit' => 'Unit',
                'base_cost_price' => 700.00,
                'base_sale_price' => 899.00,
                'base_wholesale_price' => 780.00,
                'weight' => 0.213,
                'min_stock' => 5,
                'max_stock' => 50,
                'has_spec' => 1,
                'status' => 1,
            ],
            // Laptops
            [
                'name' => 'MacBook Pro 16" M3 Max',
                'sku_prefix' => 'MACPRO',
                'category_id' => 3,
                'brand' => 'Apple',
                'base_unit' => 'Unit',
                'base_cost_price' => 2800.00,
                'base_sale_price' => 3499.00,
                'base_wholesale_price' => 3100.00,
                'weight' => 2.14,
                'min_stock' => 3,
                'max_stock' => 30,
                'has_spec' => 1,
                'status' => 1,
            ],
            [
                'name' => 'Dell XPS 15',
                'sku_prefix' => 'DELLXPS',
                'category_id' => 3,
                'brand' => 'Dell',
                'base_unit' => 'Unit',
                'base_cost_price' => 1400.00,
                'base_sale_price' => 1799.00,
                'base_wholesale_price' => 1550.00,
                'weight' => 1.86,
                'min_stock' => 5,
                'max_stock' => 50,
                'has_spec' => 1,
                'status' => 1,
            ],
            [
                'name' => 'ThinkPad X1 Carbon',
                'sku_prefix' => 'TPLENOVO',
                'category_id' => 3,
                'brand' => 'Lenovo',
                'base_unit' => 'Unit',
                'base_cost_price' => 1300.00,
                'base_sale_price' => 1699.00,
                'base_wholesale_price' => 1450.00,
                'weight' => 1.12,
                'min_stock' => 5,
                'max_stock' => 40,
                'has_spec' => 1,
                'status' => 1,
            ],
            // Tablets
            [
                'name' => 'iPad Pro 12.9"',
                'sku_prefix' => 'IPADPRO',
                'category_id' => 4,
                'brand' => 'Apple',
                'base_unit' => 'Unit',
                'base_cost_price' => 850.00,
                'base_sale_price' => 1099.00,
                'base_wholesale_price' => 950.00,
                'weight' => 0.682,
                'min_stock' => 8,
                'max_stock' => 60,
                'has_spec' => 1,
                'status' => 1,
            ],
            [
                'name' => 'Samsung Galaxy Tab S9',
                'sku_prefix' => 'TABGALAXY',
                'category_id' => 4,
                'brand' => 'Samsung',
                'base_unit' => 'Unit',
                'base_cost_price' => 650.00,
                'base_sale_price' => 849.00,
                'base_wholesale_price' => 720.00,
                'weight' => 0.500,
                'min_stock' => 6,
                'max_stock' => 50,
                'has_spec' => 1,
                'status' => 1,
            ],
            // Accessories
            [
                'name' => 'AirPods Pro 2',
                'sku_prefix' => 'AIRPODS',
                'category_id' => 5,
                'brand' => 'Apple',
                'base_unit' => 'Unit',
                'base_cost_price' => 180.00,
                'base_sale_price' => 249.00,
                'base_wholesale_price' => 210.00,
                'weight' => 0.051,
                'min_stock' => 20,
                'max_stock' => 200,
                'has_spec' => 0,
                'status' => 1,
            ],
            [
                'name' => 'Wireless Mouse MX Master 3',
                'sku_prefix' => 'MOUSE',
                'category_id' => 5,
                'brand' => 'Logitech',
                'base_unit' => 'Unit',
                'base_cost_price' => 70.00,
                'base_sale_price' => 99.00,
                'base_wholesale_price' => 85.00,
                'weight' => 0.141,
                'min_stock' => 15,
                'max_stock' => 150,
                'has_spec' => 0,
                'status' => 1,
            ],
            // Men's Wear
            [
                'name' => 'Classic Cotton T-Shirt',
                'sku_prefix' => 'TSHIRT',
                'category_id' => 8,
                'brand' => 'BasicWear',
                'base_unit' => 'Piece',
                'base_cost_price' => 15.00,
                'base_sale_price' => 35.00,
                'base_wholesale_price' => 22.00,
                'weight' => 0.200,
                'min_stock' => 50,
                'max_stock' => 500,
                'has_spec' => 1,
                'status' => 1,
            ],
            [
                'name' => 'Slim Fit Jeans',
                'sku_prefix' => 'JEANS',
                'category_id' => 8,
                'brand' => 'DenimCo',
                'base_unit' => 'Piece',
                'base_cost_price' => 35.00,
                'base_sale_price' => 79.00,
                'base_wholesale_price' => 50.00,
                'weight' => 0.450,
                'min_stock' => 30,
                'max_stock' => 300,
                'has_spec' => 1,
                'status' => 1,
            ],
            // Women's Wear
            [
                'name' => 'Summer Dress',
                'sku_prefix' => 'DRESS',
                'category_id' => 9,
                'brand' => 'FashionHouse',
                'base_unit' => 'Piece',
                'base_cost_price' => 40.00,
                'base_sale_price' => 89.00,
                'base_wholesale_price' => 55.00,
                'weight' => 0.250,
                'min_stock' => 25,
                'max_stock' => 200,
                'has_spec' => 1,
                'status' => 1,
            ],
            // Shoes
            [
                'name' => 'Running Shoes Air Max',
                'sku_prefix' => 'SHOE',
                'category_id' => 10,
                'brand' => 'SportMax',
                'base_unit' => 'Pair',
                'base_cost_price' => 65.00,
                'base_sale_price' => 129.00,
                'base_wholesale_price' => 85.00,
                'weight' => 0.350,
                'min_stock' => 20,
                'max_stock' => 150,
                'has_spec' => 1,
                'status' => 1,
            ],
            // Snacks
            [
                'name' => 'Premium Mixed Nuts',
                'sku_prefix' => 'NUTS',
                'category_id' => 12,
                'brand' => 'NutriSnack',
                'base_unit' => 'Pack',
                'base_cost_price' => 8.00,
                'base_sale_price' => 18.00,
                'base_wholesale_price' => 12.00,
                'weight' => 0.500,
                'min_stock' => 100,
                'max_stock' => 1000,
                'has_spec' => 0,
                'status' => 1,
            ],
            [
                'name' => 'Dark Chocolate Bar 70%',
                'sku_prefix' => 'CHOCO',
                'category_id' => 12,
                'brand' => 'ChocoLux',
                'base_unit' => 'Bar',
                'base_cost_price' => 3.00,
                'base_sale_price' => 7.50,
                'base_wholesale_price' => 5.00,
                'weight' => 0.100,
                'min_stock' => 200,
                'max_stock' => 2000,
                'has_spec' => 0,
                'status' => 1,
            ],
            // Beverages
            [
                'name' => 'Organic Green Tea',
                'sku_prefix' => 'TEA',
                'category_id' => 13,
                'brand' => 'TeaGarden',
                'base_unit' => 'Box',
                'base_cost_price' => 12.00,
                'base_sale_price' => 28.00,
                'base_wholesale_price' => 18.00,
                'weight' => 0.200,
                'min_stock' => 80,
                'max_stock' => 800,
                'has_spec' => 0,
                'status' => 1,
            ],
            [
                'name' => 'Arabica Coffee Beans',
                'sku_prefix' => 'COFFEE',
                'category_id' => 13,
                'brand' => 'BeanMaster',
                'base_unit' => 'Bag',
                'base_cost_price' => 15.00,
                'base_sale_price' => 35.00,
                'base_wholesale_price' => 22.00,
                'weight' => 0.500,
                'min_stock' => 60,
                'max_stock' => 600,
                'has_spec' => 0,
                'status' => 1,
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::create($productData);
            
            // Create SKUs for products with specs
            if ($product->has_spec) {
                $this->createSkus($product);
            } else {
                // Create single SKU for non-spec products
                ProductSku::create([
                    'product_id' => $product->id,
                    'sku_code' => $product->sku_prefix . '-001',
                    'spec_combination' => json_encode(['default' => 'Default']),
                    'cost_price' => $product->base_cost_price,
                    'sale_price' => $product->base_sale_price,
                    'wholesale_price' => $product->base_wholesale_price,
                    'status' => 1,
                ]);
            }
        }
    }

    private function createSkus($product)
    {
        $colors = ['Black', 'White', 'Silver', 'Blue'];
        $sizes = ['S', 'M', 'L', 'XL'];
        
        $skuIndex = 1;
        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                ProductSku::create([
                    'product_id' => $product->id,
                    'sku_code' => $product->sku_prefix . '-' . str_pad($skuIndex++, 3, '0', STR_PAD_LEFT),
                    'spec_combination' => json_encode(['Color' => $color, 'Size' => $size]),
                    'cost_price' => $product->base_cost_price,
                    'sale_price' => $product->base_sale_price + ($size == 'XL' ? 10 : 0),
                    'wholesale_price' => $product->base_wholesale_price,
                    'status' => 1,
                ]);
            }
        }
    }
}
