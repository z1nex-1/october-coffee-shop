<?php namespace Z1nex\Shop\Updates;

use October\Rain\Database\Updates\Seeder;
use Z1nex\Shop\Models\Product;

class AttachProductPhotos extends Seeder
{
    public function run()
    {
        require_once __DIR__ . '/seed_products.php';

        foreach (SeedProducts::$products as $row) {
            $product = Product::where('sku', $row[1])->first();
            if ($product && !$product->photo) {
                SeedProducts::attachPhoto($product, $row[9]);
            }
        }
    }
}
