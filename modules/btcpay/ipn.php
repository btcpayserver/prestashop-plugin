<?php

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2011-2014 BitPay
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * Updated to work with BTCPay server Access Tokens by Adrien Bensaïbi, adrien@adapp.tech
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/btcpay.php');

$btcpay = new BTCpay();

$post = file_get_contents('php://input');
if (!$post) {
    PrestaShopLogger::addLog('[Error] bad input', 3);
    die;
}

function get_order_field($invoice_id, $order_field) {
    $db = Db::getInstance();
    $result = array();
    $query = 'SELECT `'.$order_field.'` FROM `' . _DB_PREFIX_ . "order_bitcoin` WHERE `invoice_id`='" . $invoice_id . "';";
    $result = $db->ExecuteS($query);

    if (count($result)>0 && $result[0] !== null && $result[0][$order_field] !== null) {
          return $result[0][$order_field];
    } else {
       return null;
    }
}

function update_order_field($invoice_id, $order_field, $order_value) {
    $db = Db::getInstance();

    $query = 'UPDATE `' . _DB_PREFIX_ . 'order_bitcoin` SET `'.$order_field."`=".$order_value." WHERE `invoice_id`='" . $invoice_id . "';";

    $result = array();
    $result = $db->Execute($query);
    if (count($result)>0) {
          return $result[0];
    } else {
       return null;
    }
}

if (true === empty($post)) {
    PrestaShopLogger::addLog('[Error] Empty post', 3);
    die;
}

$json = json_decode($post, true);
$event= array();

// extended notification type
if(true === array_key_exists('event', $json) ) {
    $event = $json['event'];
} else {
    //nothing to do
    exit(1);
}

$data = array();
if(true === array_key_exists('data', $json)) {
    $data = $json['data'];
}

if (empty($json->event->code)) {
    PrestaShopLogger::addLog('[Error] Event code missing from callback.', 1);
}

$btcpay_ordermode = '';
$btcpay_ordermode = Configuration::get('btcpay_ORDERMODE');

# refactoring needed, next version
### ----------------
# Invoice created
if (true == array_key_exists('name', $event)
    && $event['name'] === "invoice_created"
    && $btcpay_ordermode === "beforepayment" ) {

   // sleep to not receive ipn notification
   // before the update of bitcoin order table
   sleep(15);

    // check if we have needed data
    if (true === empty($data)) {
        PrestaShopLogger::addLog('[Error] No data',3);
        exit(1);
    }

    if (false === array_key_exists('id', $data)) {
        PrestaShopLogger::addLog('[Error] No data id',3);
        exit(1);
    }

    if (false === array_key_exists('url', $data)) {
        PrestaShopLogger::addLog('[Error] No data url',3);
        exit(1);
    }

    // get invoice id, to go back on cart and check the amount
    $invoice_id = (string)$data['id'];
    if ( false === isset($invoice_id)) {
        PrestaShopLogger::addLog('[Error] No invoice id',3);
        exit(1);
    }

    $cart_id = get_order_field($invoice_id, 'cart_id');

    // search the invoice to get amount
    $cart_total = get_order_field($invoice_id, 'amount');

    // waiting payment
    $status_btcpay = 39;

    // on Order, just say payment processor is BTCPay
    $display_name = $btcpay->displayName;

    // fetch secure key, used to check cart comes from your prestashop
    $secure_key = $data['posData'];
    if ( false === isset($secure_key)) {
        PrestaShopLogger::addLog('[Error] No securekey',3);
        exit(1);
    }

    // rate in fiat currency
    $rate = $data['rate'];
    if ( false === isset($rate)) {
        PrestaShopLogger::addLog('[Error] No rate',3);
        exit(1);
    }

    $order_status = (int) $status_btcpay;

    // generate an order only if their is not another one with this cart
    $order_id = (int)Order::getIdByCartId($cart_id);
    if ( $order_id == null
         || $order_id == 0) {

       $btcpay->validateOrder(
            $cart_id,
            $status_btcpay,
            $cart_total,
            $display_name, //bitcoin btcpay
            null, //message should be new Message
            array(), //extravars for mail
            null, //currency special
            false, // don't touch amount
            $secure_key
       );

       $order_id = (int)Order::getIdByCartId($cart_id);

       // register order id for payment in BTC
       $db = Db::getInstance();
       $query = 'UPDATE `' . _DB_PREFIX_ . "order_bitcoin` SET `id_order`='". $order_id ."' WHERE `invoice_id`='" . $invoice_id . "';";

       $result = array();
       $result = $db->Execute($query);

       // update order_bitcoin paid amount
       $db = Db::getInstance();
       $query = 'UPDATE `' . _DB_PREFIX_ . "order_bitcoin` SET `btc_paid`='0.0' WHERE `id_order`=" . intval($order_id) . ';';
       $result = array();
       $result = $db->Execute($query);

       // update payment status
       $db = Db::getInstance();
       $query = 'UPDATE `' . _DB_PREFIX_ . "order_bitcoin` SET `status`='" . $order_status . "' WHERE `id_order`=" . intval($order_id) . ';';
       $result = array();
       $result = $db->Execute($query);

       // add Order status change to Order history table
       $new_history = new OrderHistory();
       $new_history->id_order = (int)$order_id;
       // bitcoin confirmation ok
       $new_history->changeIdOrderState((int)$order_status, (int)$order_id, true);
       //add with email is mandatory to add new order state in order_history
       $new_history->add(true);

       exit(0);
   } else {
       // Order already paid
       PrestaShopLogger::addLog('[Error] already created order',1);
       exit(1);
   }
}

# ----------------
# Payment Received
if (true == array_key_exists('name', $event)
   && $event['name'] === 'invoice_receivedPayment'
   && $btcpay_ordermode === 'afterpayment' ) {

   // sleep to not receive ipn notification
   // before the update of bitcoin order table
   sleep(15);

   // check if we have needed data
   if (true === empty($data)) {
       PrestaShopLogger::addLog('[Error] No data',3);
       exit;
   }

   if (false === array_key_exists('id', $data)) {
       PrestaShopLogger::addLog('[Error] No data id',3);
       exit;
   }

   if (false === array_key_exists('url', $data)) {
       PrestaShopLogger::addLog('[Error] No data url',3);
       exit;
   }

   // get invoice id, to go back on cart and check the amount
   $invoice_id = (string)$data['id'];
   if ( false === isset($invoice_id)) {
       PrestaShopLogger::addLog('[Error] No invoice id',3);
       exit;
   }

   $cart_id = get_order_field($invoice_id, 'cart_id');

   // search the invoice to get amount
   $cart_total = get_order_field($invoice_id, 'amount');

   // waiting confirmation
   $status_btcpay = 40;

   // on Order, just say payment processor is BTCPay
   $display_name = $btcpay->displayName;

   // fetch secure key, used to check cart comes from your prestashop
   $secure_key = $data['posData'];
   if ( false === isset($secure_key)) {
       PrestaShopLogger::addLog('[Error] No securekey',3);
       exit;
   }

   // rate in fiat currency
   $rate = $data['rate'];
   if ( false === isset($rate)) {
       PrestaShopLogger::addLog('[Error] No rate',3);
       exit;
   }

   // generate an order only if their is not another one with this cart
   $order_id = (int)Order::getIdByCartId($cart_id);
   if ( $order_id == null
        || $order_id == 0) {

       $btcpay->validateOrder(
            $cart_id,
            $status_btcpay,
            $cart_total,
            $display_name, //bitcoin btcpay
            $rate, //message
            array(), //extravars
            null, //currency special
            false, // don't touch amount
            $secure_key
       );

       $order_id = (int)Order::getIdByCartId($cart_id);

       // register order id for payment in BTC
       $db = Db::getInstance();
       $query = 'UPDATE `' . _DB_PREFIX_ . "order_bitcoin` SET `id_order`='". $order_id ."' WHERE `invoice_id`='" . $invoice_id . "';";

       $result = array();
       $result = $db->Execute($query);

       $order_status = (int)$status_btcpay;

       // update order_bitcoin paid amount
       $db = Db::getInstance();
       $query = 'UPDATE `' . _DB_PREFIX_ . "order_bitcoin` SET `btc_paid`='".$data['btcPaid']."' WHERE `id_order`=" . intval($order_id) . ';';
       $result = array();
       $result = $db->Execute($query);

       // update payment status
       $db = Db::getInstance();
       $query = 'UPDATE `' . _DB_PREFIX_ . "order_bitcoin` SET `status`='" . $order_status . "' WHERE `id_order`=" . intval($order_id) . ';';
       $result = array();
       $result = $db->Execute($query);

       // add Order status change to Order history table
       $new_history = new OrderHistory();
       $new_history->id_order = (int)$order_id;
       // bitcoin confirmation ok
       $new_history->changeIdOrderState((int)$order_status, (int)$order_id, true);
       $new_history->add(true);

       exit(0);
   } else {
       // Order already paid
       PrestaShopLogger::addLog('[Error] already paid order',1);
       exit(1);
   }
}

if (true == array_key_exists('name', $event)
  && $event['name'] === 'invoice_receivedPayment'
  && $btcpay_ordermode === 'beforepayment' ) {

    PrestaShopLogger::addLog('[Info] payment received', 1);

    if (true === empty($data)) {
        PrestaShopLogger::addLog('[Error] invalide json', 3);
        exit(1);
    }

    if (false === array_key_exists('id', $data)) {
         PrestaShopLogger::addLog('[Error] No id in data', 3);
        exit(1);
    }

    // get invoice id, to go back on cart and check the amount
    $invoice_id = (string)$data['id'];
    if ( false === isset($invoice_id)) {
        PrestaShopLogger::addLog('[Error] No invoice id',3);
        exit(1);
    }

    // fetch order id
    $db = Db::getInstance();
    $result = array();
    $order_id = null;
    $result = $db->ExecuteS("SELECT `id_order` FROM `" . _DB_PREFIX_ . "order_bitcoin` WHERE `invoice_id`='" . $invoice_id . "';");
    if (count($result) > 0 && $result[0] !== null && $result[0]['id_order'] !== null) {
        $order_id = $result[0]['id_order'];
    } else {
       PrestaShopLogger::addLog('[Error] IPN order id not found', 3);
       exit(1);
    }

    $order = new Order($order_id);

    // waiting confirmation
    $order_status = (int)40;

    // update order_bitcoin paid amount
    $db = Db::getInstance();
    $query = 'UPDATE `' . _DB_PREFIX_ . "order_bitcoin` SET `btc_paid`='".$data['btcPaid']."' WHERE `id_order`=" . intval($order_id) . ';';
    $result = array();
    $result = $db->Execute($query);

    // update payment status
    $db = Db::getInstance();
    $query = 'UPDATE `' . _DB_PREFIX_ . "order_bitcoin` SET `status`='" . $order_status . "' WHERE `id_order`=" . intval($order_id) . ';';
    $result = array();
    $result = $db->Execute($query);

    // add Order status change to Order history table
    $new_history = new OrderHistory();
    $new_history->id_order = (int)$order_id;
    // bitcoin confirmation ok
    $new_history->changeIdOrderState((int)$order_status, (int)$order_id, true);
    //add with email is mandatory to add new order state in order_history
    $new_history->add(true);

   exit(0);
}

###
# pending full payment Confirmed
# 1 to 6 confirmation depending on your setup
# see TX speed
if (true === array_key_exists('name', $event)
    && $event['name'] === 'invoice_paidInFull' ) {
    PrestaShopLogger::addLog('[Error] Paid in FULL',3);
    exit;
}

if (true === array_key_exists('name', $event)
    && $event['name'] === 'invoice_failedToConfirm'
    or $event['name'] === 'invoice_markedInvalid' ) {

    if (true === empty($data)) {
        PrestaShopLogger::addLog('[Error] invalide json', 3);
        exit;
    }

    if (false === array_key_exists('id', $data)) {
         PrestaShopLogger::addLog("[Error] No id in data", 3);
        exit;
    }

    if (false === array_key_exists('url', $data)) {
        PrestaShopLogger::addLog("[Error] No url in data", 3);
        exit;
    }

    // Get a BitPay Client to prepare for invoice fetching
    $client = new \Bitpay\Client\Client();
    if (false === isset($client) && true === empty($client)) {
        PrestaShopLogger::addLog('[Error] Failed to instanciate Client', 3);
        exit;
    }

    $serverurl_btcpay = Configuration::get('btcpay_URL');
    $client->setUri($serverurl_btcpay);

    $curlAdapter = new \Bitpay\Client\Adapter\CurlAdapter();
    if (false === isset($curlAdapter) || true === empty($curlAdapter)) {
        PrestaShopLogger::addLog('[Error] Failed to instanciate curlAdapter', 3);
        exit;
    }

    // Setting the Adapter param to a new BitPay CurlAdapter object
    $client->setAdapter($curlAdapter);

    $encrypted_key_btcpay = Configuration::get('btcpay_KEY');
    $key_btcpay = (string) $btcpay->bitpay_decrypt($encrypted_key_btcpay);
    if (true === empty($key_btcpay)) {
        PrestaShopLogger::addLog('[Error] Failed to decrypt key', 3);
        exit;
    }

    $key = new \Bitpay\PrivateKey();
    $key->setHex($key_btcpay);
    $client->setPrivateKey($key);

    $pub_btcpay = Configuration::get('btcpay_PUB');
    if (false === empty($pub_btcpay)) {
        $pubk = $key->getPublicKey();
        $client->setPublicKey($pubk);
    } else {
        PrestaShopLogger::addLog('[Error] Failed to get pubkey', 3);
        exit;
    }

    $token_btcpay = (string) $btcpay->bitpay_decrypt(Configuration::get('btcpay_TOKEN'));
    if (false === empty($token_btcpay)) {
        $_token = new \Bitpay\Token();
        $_token->setToken($token_btcpay);
        $client->setToken($_token);
    } else {
        PrestaShopLogger::addLog('[Error] Failed to decrypt token', 3);
        exit;
    }

    // handle case order id already exist
    // Setup the Invoice
    $invoice = new \Bitpay\Invoice();
    if (false === isset($invoice) || true === empty($invoice)) {
        PrestaShopLogger::addLog('[Error] Failed to instanciate Invoice', 3);
        exit;
    }

    $db = Db::getInstance();
    $result = array();
    $order_id = "";
    $result = $db->ExecuteS("SELECT `id_order` FROM `" . _DB_PREFIX_ . "order_bitcoin` WHERE `invoice_id`='" . (string)$data['id'] . "';");
    if (count($result)>0 && $result[0] !== null && $result[0]['id_order'] !== null) {
        $order_id = $result[0]['id_order'];
    } else {
       PrestaShopLogger::addLog('[Error] IPN order id not found', 3);
       exit;
    }

    $order = new Order($order_id);

    // wait for confirm
    $status_btcpay = 41;

    if($data['status'] === 'invalid' || $data['status'] === 'expired')
    {
        // time setup on invoice is expired
        $status_btcpay = 41;
    }

    $order_status = (int)$status_btcpay;

    // update amount paid
    $db = Db::getInstance();
    $query = 'UPDATE `' . _DB_PREFIX_ . "order_bitcoin` SET `status`='".$order_status."' WHERE `id_order`=" . intval($order_id) . ';';
    $result = array();
    $result = $db->Execute($query);


    // add Order status change to Order history table
    $new_history = new OrderHistory();
    $new_history->id_order = intval($order_id);
    // bitcoin confirmation ok
    $new_history->changeIdOrderState((int)$order_status, (int)$order_id, true);
    $new_history->add(true);
}

###
# Payment Confirmed
# 1 to 6 confirmation depending on your setup
# confirmed then completed
# see TX speed
if (true === array_key_exists('name', $event)
    && $event['name'] === 'invoice_confirmed' ) {

    if (true === empty($data)) {
        PrestaShopLogger::addLog('[Error] invalide json', 3);
        exit;
    }

    if (false === array_key_exists('id', $data)) {
         PrestaShopLogger::addLog("[Error] No id in data", 3);
        exit;
    }

    if (false === array_key_exists('url', $data)) {
        PrestaShopLogger::addLog("[Error] No url in data", 3);
        exit;
    }

    //event name
    //invoice_created
    //invoice_receivedPayment
    //invoice_paidInFull
    //invoice_confirmed
    //invoice_completed

    // Get a BitPay Client to prepare for invoice fetching
    $client = new \Bitpay\Client\Client();
    if (false === isset($client) && true === empty($client)) {
        PrestaShopLogger::addLog('[Error] Failed to instanciate Client', 3);
        exit;
    }

    $serverurl_btcpay = Configuration::get('btcpay_URL');
    $client->setUri($serverurl_btcpay);

    $curlAdapter = new \Bitpay\Client\Adapter\CurlAdapter();
    if (false === isset($curlAdapter) || true === empty($curlAdapter)) {
        PrestaShopLogger::addLog('[Error] Failed to instanciate curlAdapter', 3);
        exit;
    }

    // Setting the Adapter param to a new BitPay CurlAdapter object
    $client->setAdapter($curlAdapter);

    $encrypted_key_btcpay = Configuration::get('btcpay_KEY');
    $key_btcpay = (string) $btcpay->bitpay_decrypt($encrypted_key_btcpay);
    if (true === empty($key_btcpay)) {
        PrestaShopLogger::addLog('[Error] Failed to decrypt key', 3);
        exit;
    }

    $key = new \Bitpay\PrivateKey();
    $key->setHex($key_btcpay);
    $client->setPrivateKey($key);

    $pub_btcpay = Configuration::get('btcpay_PUB');
    if (false === empty($pub_btcpay)) {
        $pubk = $key->getPublicKey();
        $client->setPublicKey($pubk);
    } else {
        PrestaShopLogger::addLog('[Error] Failed to get pubkey', 3);
        exit;
    }

    $token_btcpay = (string) $btcpay->bitpay_decrypt(Configuration::get('btcpay_TOKEN'));
    if (false === empty($token_btcpay)) {
        $_token = new \Bitpay\Token();
        $_token->setToken($token_btcpay);
        $client->setToken($_token);
    } else {
        PrestaShopLogger::addLog('[Error] Failed to decrypt token', 3);
        exit;
    }

    // handle case order id already exist
    // Setup the Invoice
    $invoice = new \Bitpay\Invoice();
    if (false === isset($invoice) || true === empty($invoice)) {
        PrestaShopLogger::addLog('[Error] Failed to instanciate Invoice', 3);
        exit;
    }

    $db = Db::getInstance();
    $result = array();
    $order_id = "";
    $result = $db->ExecuteS("SELECT `id_order` FROM `" . _DB_PREFIX_ . "order_bitcoin` WHERE `invoice_id`='" . (string)$data['id'] . "';");
    if (count($result)>0 && $result[0] !== null && $result[0]['id_order'] !== null) {
        $order_id = $result[0]['id_order'];
    } else {
       PrestaShopLogger::addLog('[Error] IPN order id not found', 3);
       exit;
    }

    $order = new Order($order_id);

    // wait for confirm
    $status_btcpay = 40;

    if($data['status'] === 'invalid' || $data['status'] === 'expired')
    {
        // time setup on invoice is expired
        $status_btcpay = 41;
    }

    if($data['status'] === 'paid')
    {
        // TX received but we have to wait some confirmation
        $status_btcpay = 40;
    }

    if($data['status'] === 'confirmed' || $data['status'] === 'complete')
    {
        //Transaction confirmed
        $status_btcpay = 42;
    }

    $order_status = (int)$status_btcpay;

    // update amount paid
    $db = Db::getInstance();
    $query = 'UPDATE `' . _DB_PREFIX_ . "order_bitcoin` SET `status`='".$order_status."' WHERE `id_order`=" . intval($order_id) . ';';
    $result = array();
    $result = $db->Execute($query);

    // add Order status change to Order history table
    if ( $order->current_state != $order_status) {
        $new_history = new OrderHistory();
        $new_history->id_order = (int)$order_id;
        // bitcoin confirmation ok
        $new_history->changeIdOrderState((int)$order_status, (int)$order_id, true);
        $new_history->add(true);
    } else {
        PrestaShopLogger::addLog('[Error] current state is not different than new order status in invoice confirmed', 3);
    }
}
