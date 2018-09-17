<?php

/**
 * @deprecated 1.5.0 This file is deprecated, use moduleFrontController instead
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
//prestatshop 1.7
//include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/btcpay.php');

$context  = Context::getContext();
$cart     = $context->cart;

$btcpay   = new btcpay();

if ($cart->id_customer == 0 OR $cart->id_address_delivery == 0 OR $cart->id_address_invoice == 0 OR !$btcpay->active) {
    Tools::redirect('index.php?controller=order&step=1');
}

// Check that this payment option is still available in case the customer
// changed his address just before the end of the checkout process
$authorized = false;

foreach (Module::getPaymentModules() as $module) {
    if ($module['name'] == 'btcpay') {
        $authorized = true;
        break;
    }
}

if (!$authorized) {
    die($btcpay->l('This payment method is not available.', 'validation'));
}

echo $btcpay->execPayment($cart);
