<?php namespace Z1nex\Shop\Updates;

use October\Rain\Database\Updates\Seeder;
use Z1nex\Shop\Models\Product;

class SeedProducts extends Seeder
{
    public static $products = [
        ['Эфиопия Иргачеффе', 'ET-YRG-250', 'Эфиопия', 'мытая', 'light', 'жасмин, бергамот, лимонная цедра', 250, 890, '#c9a227'],
        ['Кения AA Ньери', 'KE-NYR-250', 'Кения', 'мытая', 'light', 'чёрная смородина, грейпфрут', 250, 1090, '#8e2c48'],
        ['Колумбия Уила', 'CO-HUI-250', 'Колумбия', 'мытая', 'medium', 'карамель, красное яблоко', 250, 790, '#c4552f'],
        ['Бразилия Серрадо', 'BR-CER-1000', 'Бразилия', 'натуральная', 'medium', 'молочный шоколад, фундук', 1000, 1890, '#3f6b4a'],
        ['Гватемала Антигуа', 'GT-ANT-250', 'Гватемала', 'мытая', 'medium', 'какао, апельсин, тростниковый сахар', 250, 850, '#2f5a8a'],
        ['Руанда Ньямашеке', 'RW-NYA-250', 'Руанда', 'мытая', 'light', 'клюква, чёрный чай', 250, 990, '#b5476b'],
        ['Эспрессо-смесь «Утро»', 'BL-MRN-1000', 'Бразилия, Колумбия', 'натуральная, мытая', 'dark', 'тёмный шоколад, жареный орех', 1000, 1690, '#4a3428'],
        ['Колумбия декаф', 'CO-DEC-250', 'Колумбия', 'Swiss Water', 'medium', 'ваниль, печёное яблоко', 250, 820, '#6d6a8e'],
    ];

    public function run()
    {
        foreach (self::$products as $i => $row) {
            list($name, $sku, $origin, $process, $roast, $notes, $weight, $price, $color) = $row;

            Product::create([
                'name'        => $name,
                'slug'        => str_slug($name),
                'sku'         => $sku,
                'origin'      => $origin,
                'process'     => $process,
                'roast'       => $roast,
                'notes'       => $notes,
                'description' => "Обжариваем под заказ, отправляем на следующий день после обжарки. Ноты: {$notes}.",
                'weight'      => $weight,
                'price'       => $price,
                'color'       => $color,
                'sort_order'  => $i + 1,
            ]);
        }
    }
}
