<?php namespace Z1nex\Shop\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

class Orders extends Controller
{
    public $implement = [
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\FormController::class,
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['z1nex.shop.manage_orders'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Z1nex.Shop', 'shop', 'orders');
    }
}
