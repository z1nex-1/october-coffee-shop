<?php namespace Z1nex\Shop;

use Backend;
use Config;
use Illuminate\Http\Request;
use System\Classes\PluginBase;
use Z1nex\Shop\Classes\Payments\Gateway;
use Z1nex\Shop\Classes\Payments\SandboxGateway;
use Z1nex\Shop\Classes\Payments\YooKassaGateway;
use Z1nex\Shop\Classes\Delivery\CdekClient;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'Магазин',
            'description' => 'Каталог, корзина, оплата через ЮKassa и доставка СДЭК',
            'author'      => 'z1nex',
            'icon'        => 'icon-shopping-cart',
        ];
    }

    public function register()
    {
        $this->app->singleton(Gateway::class, function () {
            if (Config::get('z1nex.shop::payment.driver') === 'yookassa') {
                return new YooKassaGateway(
                    Config::get('z1nex.shop::payment.yookassa.shop_id'),
                    Config::get('z1nex.shop::payment.yookassa.secret_key')
                );
            }
            return new SandboxGateway;
        });

        $this->app->singleton(CdekClient::class, function () {
            return new CdekClient(Config::get('z1nex.shop::cdek'));
        });

        $this->registerConsoleCommand('shop.reconcile', Console\ReconcilePayments::class);
        $this->registerConsoleCommand('shop.reset-demo', Console\ResetDemo::class);
    }

    public function boot()
    {
        // за Caddy без доверенного прокси October строит http-ссылки на https-странице
        $proxies = array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES'))));
        if ($proxies) {
            Request::setTrustedProxies($proxies, Request::HEADER_X_FORWARDED_ALL);
        }
    }

    public function registerComponents()
    {
        return [
            Components\Catalog::class     => 'shopCatalog',
            Components\Cart::class        => 'shopCart',
            Components\Checkout::class    => 'shopCheckout',
            Components\OrderStatus::class => 'shopOrderStatus',
        ];
    }

    public function registerPermissions()
    {
        return [
            'z1nex.shop.manage_orders' => [
                'tab'   => 'Магазин',
                'label' => 'Заказы',
            ],
            'z1nex.shop.manage_products' => [
                'tab'   => 'Магазин',
                'label' => 'Товары',
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'shop' => [
                'label'       => 'Магазин',
                'url'         => Backend::url('z1nex/shop/orders'),
                'icon'        => 'icon-shopping-cart',
                'permissions' => ['z1nex.shop.*'],
                'order'       => 300,
                'sideMenu'    => [
                    'orders' => [
                        'label'       => 'Заказы',
                        'icon'        => 'icon-list-alt',
                        'url'         => Backend::url('z1nex/shop/orders'),
                        'permissions' => ['z1nex.shop.manage_orders'],
                    ],
                    'products' => [
                        'label'       => 'Товары',
                        'icon'        => 'icon-coffee',
                        'url'         => Backend::url('z1nex/shop/products'),
                        'permissions' => ['z1nex.shop.manage_products'],
                    ],
                ],
            ],
        ];
    }

    public function registerSchedule($schedule)
    {
        $schedule->command('shop:reconcile-payments')->everyFiveMinutes();

        if (Config::get('z1nex.shop::demo.nightly_reset')) {
            $schedule->command('shop:reset-demo')->dailyAt('04:00');
        }
    }
}
