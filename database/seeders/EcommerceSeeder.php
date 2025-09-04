<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\ProductCategories;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductAttribute;
use App\Models\AttributeValue;
use App\Models\ProductAttributeValue;

class EcommerceSeeder extends Seeder
{
    public function run(): void
    {
        // // Categories
        // $categories = [
        //     ['name' => 'Notebooks', 'image' => 'http://magic-app.test/public/product-category-icons/cat1.png'],
        //     ['name' => 'Office Accessories', 'image' => 'http://magic-app.test/public/product-category-icons/cat2.png'],
        //     ['name' => 'Electronics', 'image' => 'http://magic-app.test/public/product-category-icons/cat3.png'],
        //     ['name' => 'Meditation', 'image' => 'http://magic-app.test/public/product-category-icons/cat4.png'],
        // ];

        // $categoryIds = [];
        // foreach ($categories as $cat) {
        //     $category = ProductCategories::create([
        //         'name' => $cat['name'],
        //         'slug' => Str::slug($cat['name']),
        //         'image' => $cat['image'],
        //         'description' => $cat['name'] . ' description',
        //         'status' => true,
        //     ]);
        //     $categoryIds[] = $category->id;
        // }

        // Products with images
        // $products = [
        //     [
        //         'name' => 'Book 1',
        //         'image' => 'http://magic-app.test/public/product-images/book1.png',
        //         'extra_images' => [
        //             'http://magic-app.test/public/product-images/book2.png',
        //             'http://magic-app.test/public/product-images/book3.png',
        //             'http://magic-app.test/public/product-images/book4.png',
        //         ],
        //     ],
        //     [
        //         'name' => 'Book 2',
        //         'image' => 'http://magic-app.test/public/product-images/book2.png',
        //         'extra_images' => [
        //             'http://magic-app.test/public/product-images/book1.png',
        //             'http://magic-app.test/public/product-images/book3.png',
        //         ],
        //     ],
        //     [
        //         'name' => 'Book 3',
        //         'image' => 'http://magic-app.test/public/product-images/book3.png',
        //         'extra_images' => [
        //             'http://magic-app.test/public/product-images/book1.png',
        //             'http://magic-app.test/public/product-images/book4.png',
        //         ],
        //     ],
        //     [
        //         'name' => 'Book 4',
        //         'image' => 'http://magic-app.test/public/product-images/book4.png',
        //         'extra_images' => [
        //             'http://magic-app.test/public/product-images/book2.png',
        //             'http://magic-app.test/public/product-images/book3.png',
        //         ],
        //     ],
        // ];

        // $productIds = [];
        // foreach ($products as $index => $prod) {
        //     $product = Product::create([
        //         'category_id' => $categoryIds[$index % count($categoryIds)],
        //         'name' => $prod['name'],
        //         'slug' => Str::slug($prod['name']),
        //         'sku' => 'SKU' . ($index + 1),
        //         'description' => $prod['name'] . ' full description',
        //         'price' => rand(100, 500),
        //         'sale_price' => rand(80, 400),
        //         'stock' => rand(10, 50),
        //         'status' => true,
        //     ]);

        //     $productIds[] = $product->id;

        //     // Main image as ProductImage
        //     ProductImage::create([
        //         'product_id' => $product->id,
        //         'url' => $prod['image'],
        //         'sort_order' => 0,
        //     ]);

        //     // Extra images
        //     foreach ($prod['extra_images'] as $key => $img) {
        //         ProductImage::create([
        //             'product_id' => $product->id,
        //             'url' => $img,
        //             'sort_order' => $key + 1,
        //         ]);
        //     }
        // }

        // Attributes Example
        // $colorAttr = ProductAttribute::create([
        //     'name' => 'Color',
        //     'slug' => 'color',
        // ]);

        // $sizeAttr = ProductAttribute::create([
        //     'name' => 'Size',
        //     'slug' => 'size',
        // ]);

        // $colorValues = [];
        // $colors = ['Red', 'Blue', 'Green'];
        // foreach ($colors as $color) {
        //     $colorValues[] =  AttributeValue::create([
        //         'attribute_id' => $colorAttr->id,
        //         'value' => $color,
        //         'slug' => Str::slug($color),
        //     ]);
        // }

        // $sizeValues = [];
        // $sizes = ['Small', 'Medium', 'Large'];
        // foreach ($sizes as $size) {
        //     $sizeValues = []; AttributeValue::create([
        //         'attribute_id' => $sizeAttr->id,
        //         'value' => $size,
        //         'slug' => Str::slug($size),
        //     ]);
        // }

        // foreach ($productIds as $pid) {
        //     ProductAttributeValue::create([
        //         'product_id' => $pid,
        //         'attribute_value_id' => $colorValues[array_rand($colorValues)],
        //     ]);

        //     ProductAttributeValue::create([
        //         'product_id' => $pid,
        //         'attribute_value_id' => $sizeValues[array_rand($sizeValues)],
        //     ]);
        // }
    }
}
