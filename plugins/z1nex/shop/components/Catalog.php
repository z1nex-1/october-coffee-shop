<?php namespace Z1nex\Shop\Components;

use Cms\Classes\ComponentBase;
use Z1nex\Shop\Models\Product;

class Catalog extends ComponentBase
{
    public $products;
    public $roasts;
    public $roast;

    public function componentDetails()
    {
        return [
            'name'        => 'Каталог',
            'description' => 'Список товаров с фильтром по обжарке',
        ];
    }

    public function onRun()
    {
        $this->prepare(get('roast'));
    }

    public function onFilter()
    {
        $this->prepare(post('roast'));
    }

    protected function prepare($roast)
    {
        $this->roasts = Product::$roasts;
        $this->roast = array_key_exists($roast, $this->roasts) ? $roast : null;

        $query = Product::active();
        if ($this->roast) {
            $query->where('roast', $this->roast);
        }
        $this->products = $query->get();
    }
}
