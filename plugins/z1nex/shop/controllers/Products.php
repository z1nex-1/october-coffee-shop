<?php namespace Z1nex\Shop\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

class Products extends Controller
{
    public $implement = [
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ReorderController::class,
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = ['z1nex.shop.manage_products'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Z1nex.Shop', 'shop', 'products');
    }
}
