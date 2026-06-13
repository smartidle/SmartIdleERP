<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics', 'code' => 'ELECTRONICS', 'parent_id' => 0, 'sort' => 1, 'status' => 1],
            ['name' => 'Mobile Phones', 'code' => 'PHONE', 'parent_id' => 1, 'sort' => 1, 'status' => 1],
            ['name' => 'Laptops', 'code' => 'LAPTOP', 'parent_id' => 1, 'sort' => 2, 'status' => 1],
            ['name' => 'Tablets', 'code' => 'TABLET', 'parent_id' => 1, 'sort' => 3, 'status' => 1],
            ['name' => 'Accessories', 'code' => 'ACCESSORY', 'parent_id' => 1, 'sort' => 4, 'status' => 1],
            ['name' => 'Computers', 'code' => 'COMPUTER', 'parent_id' => 1, 'sort' => 5, 'status' => 1],
            ['name' => 'Clothing', 'code' => 'CLOTHING', 'parent_id' => 0, 'sort' => 2, 'status' => 1],
            ['name' => 'Men\'s Wear', 'code' => 'MENS', 'parent_id' => 7, 'sort' => 1, 'status' => 1],
            ['name' => 'Women\'s Wear', 'code' => 'WOMENS', 'parent_id' => 7, 'sort' => 2, 'status' => 1],
            ['name' => 'Shoes', 'code' => 'SHOES', 'parent_id' => 7, 'sort' => 3, 'status' => 1],
            ['name' => 'Food & Beverage', 'code' => 'FOOD', 'parent_id' => 0, 'sort' => 3, 'status' => 1],
            ['name' => 'Snacks', 'code' => 'SNACKS', 'parent_id' => 11, 'sort' => 1, 'status' => 1],
            ['name' => 'Beverages', 'code' => 'BEVERAGES', 'parent_id' => 11, 'sort' => 2, 'status' => 1],
            ['name' => 'Home & Garden', 'code' => 'HOME', 'parent_id' => 0, 'sort' => 4, 'status' => 1],
            ['name' => 'Furniture', 'code' => 'FURNITURE', 'parent_id' => 14, 'sort' => 1, 'status' => 1],
            ['name' => 'Office Supplies', 'code' => 'OFFICE', 'parent_id' => 0, 'sort' => 5, 'status' => 1],
        ];

        foreach ($categories as $category) {
            ProductCategory::create($category);
        }
    }
}
