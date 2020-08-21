<?php

class AdminBTCpayController extends ModuleAdminController
{
    /**
     * BTCpay module instance. It's assigned automatically by PrestaShop  infrastructure.
     *
     * @var BTCpay
     */
    public $module;

    /**
     * Configure the administration controller and define some sane defaults.
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        parent::__construct();

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }
    }

    /**
     * Render the main administration screen of the module.
     */
    public function renderView()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->module->name);
    }
}
