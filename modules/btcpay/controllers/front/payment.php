<?php

class BTCpayPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @var BTCpay
     */
    public $module;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
        $cart = $this->context->cart;

        echo $this->module->execPayment($cart);
    }
}

