<?php namespace Z1nex\Shop\Models;

use Model;

class Product extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;

    public $table = 'z1nex_shop_products';

    protected $fillable = [
        'name', 'slug', 'sku', 'origin', 'process', 'roast', 'notes',
        'description', 'weight', 'price', 'color', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price'     => 'integer',
        'weight'    => 'integer',
    ];

    public $rules = [
        'name'   => 'required',
        'slug'   => 'required|unique:z1nex_shop_products',
        'price'  => 'required|integer|min:1',
        'weight' => 'required|integer|min:1',
        'color'  => ['regex:/^#[0-9a-fA-F]{6}$/'],
    ];

    public $attachOne = [
        'photo' => ['System\Models\File', 'delete' => true],
    ];

    public static $roasts = [
        'light'  => 'светлая',
        'medium' => 'средняя',
        'dark'   => 'тёмная',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function getRoastOptions()
    {
        return self::$roasts;
    }

    public function getRoastLabelAttribute()
    {
        return array_get(self::$roasts, $this->roast, $this->roast);
    }

    public function getRoastLevelAttribute()
    {
        return array_search($this->roast, array_keys(self::$roasts)) + 1;
    }

    public function thumb($size)
    {
        return $this->photo ? $this->photo->getThumb($size, $size, ['mode' => 'crop']) : null;
    }

    public function getWeightLabelAttribute()
    {
        return $this->weight >= 1000 ? ($this->weight / 1000) . ' кг' : $this->weight . ' г';
    }
}
