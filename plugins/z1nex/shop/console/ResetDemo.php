<?php namespace Z1nex\Shop\Console;

use Db;
use Illuminate\Console\Command;
use Z1nex\Shop\Models\Product;
use Z1nex\Shop\Updates\SeedProducts;

class ResetDemo extends Command
{
    protected $name = 'shop:reset-demo';

    protected $description = 'Очистить заказы и вернуть каталог к исходному состоянию (для демо-стенда)';

    public function handle()
    {
        Db::transaction(function () {
            Product::with('photo')->get()->each(function ($product) {
                $product->photo && $product->photo->delete();
            });

            foreach (['z1nex_shop_payment_events', 'z1nex_shop_order_items', 'z1nex_shop_orders', 'z1nex_shop_sandbox_payments', 'z1nex_shop_products'] as $table) {
                Db::table($table)->delete();
            }

            require_once plugins_path('z1nex/shop/updates/seed_products.php');
            (new SeedProducts)->run();
        });

        $this->info('Демо-данные сброшены');
    }
}
