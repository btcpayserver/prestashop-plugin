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
 * Originally written by Kris, 2012
 * Updated to work with Prestashop 1.6 by Rich Morgan, rich@btcpay.com
 * Updated to work with BTCPay server Access Tokens by Adrien Bensaïbi, adrien@adapp.tech
 *
 * TODO check 1.6
 */

use Bitpay\Autoloader;
use Bitpay\Buyer;
use Bitpay\Client\Adapter\CurlAdapter;
use Bitpay\Client\Client;
use Bitpay\Crypto\OpenSSLExtension;
use Bitpay\Currency as BitpayCurrency;
use Bitpay\Invoice;
use Bitpay\Item;
use Bitpay\PrivateKey;
use Bitpay\PublicKey;
use Bitpay\SinKey;
use Bitpay\Token;
use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderPresenter;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BTCpay extends \PaymentModule
{
    protected $_postErrors = [];

    /**
     * @var string
     */
    private $btcpayurl;

    public $tabs = [[
        'name' => 'BTCPay',
        'visible' => true,
        'class_name' => 'AdminBTCpay',
        'parent_class_name' => 'AdminParentPayment',
    ]];

    public function __construct()
    {
        $autoloader = __DIR__ . '/lib/Bitpay/Autoloader.php';

        // Load up the BitPay library
        if (true === file_exists($autoloader) && true === is_readable($autoloader)) {
            require_once $autoloader;
            Autoloader::register();
        } else {
            throw new \RuntimeException('BitPay Library could not be loaded');
        }

        $this->name = 'btcpay';
        $this->tab = 'payments_gateways';
        $this->version = '2.1.1';
        $this->author = 'BTCPayServer';
        $this->is_eu_compatible = 1;
        $this->ps_versions_compliancy = ['min' => '1.7.6', 'max' => _PS_VERSION_];
        $this->controllers = ['payment', 'validation'];
        $this->bootstrap = true;

        $this->currencies = true;
        $this->currencies_mode = 'radio';

        $this->btcpayurl = '';

        parent::__construct();

        $this->displayName = $this->l('BTCPay');
        $this->description = $this->l('Accepts Bitcoin payments via BTCPay.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
    }

    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('invoice')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('paymentOptions')) {
            return false;
        }

        $db = Db::getInstance();

        // order payment in BTC
        // maybe add module version
        $query = 'CREATE TABLE `' . _DB_PREFIX_ . 'order_bitcoin` (
                `id_payment` int(11) NOT NULL AUTO_INCREMENT,
                `cart_id` int(11) NOT NULL,
                `id_order` int(11),
                `status` varchar(255) NOT NULL,
                `invoice_id` varchar(255),
                `amount` varchar(255),
                `btc_price` varchar(255),
                `btc_paid` varchar(255),
                `btc_address` varchar(255),
                `btc_refundaddress` varchar(255),
                `redirect` varchar(255),
                `rate` varchar(255),
                PRIMARY KEY (`id_payment`),
                UNIQUE KEY `invoice_id` (`invoice_id`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        $db->Execute($query);

        // We actually use
        // 39 - want to pay in bitcoin
        // 40 - waiting for confirmation
        // 41 - payment failed
        // 42 - payment confirm
        // look into (SELECT * FROM mod783_order_state_lang)
        // to be sure not other plugin do that.
        // TODO maybe take the last number available

        $query = 'INSERT INTO `' . _DB_PREFIX_ . "order_state_lang` (`id_order_state`,`id_lang`,`name`,`template`) VALUES ('39','1','Awaiting Bitcoin payment','bitcoin_want');";
        $db->Execute($query);
        $query = 'INSERT INTO `' . _DB_PREFIX_ . "order_state` (`id_order_state`, `invoice`, `send_email`, `module_name`, `color`, `unremovable`, `hidden`, `logable`, `delivery`, `shipped`, `paid`, `pdf_invoice`, `pdf_delivery`, `deleted`) VALUES ('39', '0', '0', 'btcpay', '#FF8C00', '1', '0', '0', '0', '0', '0', '0', '0', '0');";
        $db->Execute($query);

        $query = 'INSERT INTO `' . _DB_PREFIX_ . "order_state_lang` (`id_order_state`,`id_lang`,`name`,`template`) VALUES ('40','1','Waiting for Bitcoin confirmations','bitcoin_waiting');";
        $db->Execute($query);
        $query = 'INSERT INTO `' . _DB_PREFIX_ . "order_state` (`id_order_state`, `invoice`, `send_email`, `module_name`, `color`, `unremovable`, `hidden`, `logable`, `delivery`, `shipped`, `paid`, `pdf_invoice`, `pdf_delivery`, `deleted`) VALUES ('40', '0', '0', 'btcpay', '#4169E1', '1', '0', '0', '0', '0', '0', '0', '0', '0');";
        $db->Execute($query);

        $query = 'INSERT INTO `' . _DB_PREFIX_ . "order_state_lang` (`id_order_state`,`id_lang`,`name`,`template`) VALUES ('41','1','Bitcoin transaction failed','bitcoin_invalid');";
        $db->Execute($query);
        $query = 'INSERT INTO `' . _DB_PREFIX_ . "order_state` (`id_order_state`, `invoice`, `send_email`, `module_name`, `color`, `unremovable`, `hidden`, `logable`, `delivery`, `shipped`, `paid`, `pdf_invoice`, `pdf_delivery`, `deleted`) VALUES ('41', '0', '0', 'btcpay', '#EC2E15', '1', '0', '1', '0', '0', '0', '0', '0', '0');";
        $db->Execute($query);

        $query = 'INSERT INTO `' . _DB_PREFIX_ . "order_state_lang` (`id_order_state`,`id_lang`,`name`,`template`) VALUES ('42','1','Paid with Bitcoin','bitcoin_confirm');";
        $db->Execute($query);
        $query = 'INSERT INTO `' . _DB_PREFIX_ . "order_state` (`id_order_state`, `invoice`, `send_email`, `module_name`, `color`, `unremovable`, `hidden`, `logable`, `delivery`, `shipped`, `paid`, `pdf_invoice`, `pdf_delivery`, `deleted`) VALUES ('42', '0', '0', 'btcpay', '#108510', '1', '0', '1', '0', '0', '1', '1', '0', '0');";
        $db->Execute($query);

        // insert module install timestamp
        $query = 'INSERT IGNORE INTO `' . _DB_PREFIX_ . "configuration` (`name`, `value`, `date_add`, `date_upd`) VALUES ('PS_OS_BTCPAY', '13', NOW(), NOW());";
        $db->Execute($query);

        //init clear configurations
        Configuration::updateValue('btcpay_URL', '');
        Configuration::updateValue('btcpay_LABEL', '');
        Configuration::updateValue('btcpay_PAIRINGCODE', '');
        Configuration::updateValue('btcpay_KEY', '');
        Configuration::updateValue('btcpay_PUB', '');
        Configuration::updateValue('btcpay_SIN', '');
        Configuration::updateValue('btcpay_TOKEN', '');
        Configuration::updateValue('btcpay_TXSPEED', '');
        Configuration::updateValue('btcpay_ORDERMODE', '');

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('btcpay_ORDERMODE');
        Configuration::deleteByName('btcpay_TXSPEED');
        Configuration::deleteByName('btcpay_TOKEN');
        Configuration::deleteByName('btcpay_SIN');
        Configuration::deleteByName('btcpay_PUB');
        Configuration::deleteByName('btcpay_KEY');
        Configuration::deleteByName('btcpay_PARINGCODE');
        Configuration::deleteByName('btcpay_LABEL');
        Configuration::deleteByName('btcpay_URL');

        $db = Db::getInstance();
        $query = 'DROP TABLE `' . _DB_PREFIX_ . 'order_bitcoin`';
        $db->Execute($query);

        return parent::uninstall();
    }

    public function getContent(): string
    {
        $result = $this->_postProcess();

        // Get BTCPay URL or use sane default
        $btcpayurl = Configuration::get('btcpay_URL');
        if (true === empty($btcpayurl)) {
            $btcpayurl = 'https://testnet.demo.btcpayserver.org';
        }

        $this->context->smarty->assign([
            'btcpayurl' => $btcpayurl,
            'txSpeed' => Configuration::get('btcpay_TXSPEED'),
            'orderMode' => Configuration::get('btcpay_ORDERMODE'),
            'formBTCPayURL' => Tools::getValue('serverurl', Configuration::get('btcpay_URL')),
            'formPairingCode' => Tools::getValue('pairingcode', Configuration::get('btcpay_PAIRINGCODE')),
        ]);

        return $result . $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return null;
        }

        $paymentOption = new PaymentOption();
        $paymentOption->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/images/bitcoin.png'));
        $paymentOption->setModuleName($this->name);
        $paymentOption->setCallToActionText($this->l('Pay with Bitcoin'));
        $paymentOption->setAction(Configuration::get('PS_FO_PROTOCOL') . __PS_BASE_URI__ . "modules/{$this->name}/payment.php");

        return [$paymentOption];
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('submitpairing')) {
            $this->_errors = [];

            if (Tools::getValue('form_btcpay_pairingcode') == null) {
                $this->_errors[] = $this->l('Missing Pairing Code');
            }

            if (Tools::getValue('form_btcpay_url') == null) {
                $this->_errors[] = $this->l('Missing BTCPay server url');
            }

            // ONly update the pairing code if it's different
            if (Tools::getValue('form_btcpay_pairingcode') !== Configuration::get('btcpay_PAIRINGCODE')) {
                $this->_ajax_bitpay_pair_code(
                    Tools::getValue('form_btcpay_pairingcode'),
                    Tools::getValue('form_btcpay_url')
                );
            }

            if (count($this->_errors) > 0) {
                $error_msg = '';

                foreach ($this->_errors as $error) {
                    $error_msg .= $error . '<br />';
                }

                return $this->displayError($error_msg);
            }

            Configuration::updateValue('btcpay_ORDERMODE', trim(Tools::getValue('form_btcpay_ordermode')));
            Configuration::updateValue('btcpay_TXSPEED', trim(Tools::getValue('form_btcpay_txspeed')));
            Configuration::updateValue('btcpay_PAIRINGCODE', trim(Tools::getValue('form_btcpay_pairingcode')));
            Configuration::updateValue('btcpay_URL', trim(Tools::getValue('form_btcpay_url')));

            return $this->displayConfirmation($this->l('Settings updated'));
        }

        return null;
    }

    private function _ajax_bitpay_pair_code($pairing_code, $btcpay_url)
    {
        $_btcpay_url = $btcpay_url;

        // check pairing code
        if (true === isset($pairing_code) && trim($pairing_code) !== '') {
            // Validate the Pairing Code
            $pairing_code = trim($pairing_code);
        } else {
            $this->_errors[] = $this->l('Missing Pairing Code');

            return null;
        }

        if (!preg_match('/^[a-zA-Z0-9]{7}$/', $pairing_code)) {
            $this->_errors[] = $this->l('Invalid Pairing Code');

            return null;
        }

        // check btcpayserver hosting url
        if ((substr($_btcpay_url, 0, 7) !== 'http://' && substr($_btcpay_url, 0, 8) !== 'https://')) {
            $this->_errors[] = $this->l('Invalid BTCPay server url');

            return null;
        }

        // Generate Private Key for api security
        $key = new PrivateKey();
        if (true === empty($key)) {
            $this->_errors[] = $this->l('The BTCPay payment plugin was called to process a pairing code but could not instantiate a PrivateKey object. Cannot continue!');

            return null;
        }
        $key->generate();

        // Generate Public Key
        $pub = new PublicKey();
        if (true === empty($pub)) {
            $this->_errors[] = $this->l('The BTCPay payment plugin was called to process a pairing code but could not instantiate a PublicKey object. Cannot continue!');

            return null;
        }
        $pub->setPrivateKey($key);
        $pub->generate();

        // Get SIN Key
        $sin = new SinKey();
        if (true === empty($sin)) {
            $this->_errors[] = $this->l('The BTCPay payment plugin was called to process a pairing code but could not instantiate a SinKey object. Cannot continue!');

            return null;
        }

        $sin->setPublicKey($pub);
        $sin->generate();

        // Create an API Client
        $client = new Client();
        if (true === empty($client)) {
            $this->_errors[] = $this->l('The BTCPay payment plugin was called to process a pairing code but could not instantiate a Client object. Cannot continue!');

            return null;
        }

        $client->setUri($_btcpay_url);
        $curlAdapter = new CurlAdapter();
        if (true === empty($curlAdapter)) {
            $this->_errors[] = $this->l('The BTCPay payment plugin was called to process a pairing code but could not instantiate a CurlAdapter object. Cannot continue!');

            return null;
        }

        $client->setAdapter($curlAdapter);
        $client->setPrivateKey($key);
        $client->setPublicKey($pub);
        $label = 'token_prestashop';

        try {
            $token = $client->createToken(
                [
                    'id' => (string) $sin,
                    'pairingCode' => (string) $pairing_code,
                    'label' => $label,
                ]
            );
        } catch (Exception $e) {
            PrestaShopLogger::addLog('error:' . $e->getMessage(), 2);
        }

        if (false === isset($token)) {
            $this->_errors[] = $this->l('Failed to create token, you are maybe using an already activated pairing code.');
        }

        if (count($this->_errors) > 0) {
            $error_msg = '';

            foreach ($this->_errors as $error) {
                $error_msg .= $error . '<br />';
            }

            return $this->displayError($error_msg);
        }

        Configuration::updateValue('btcpay_URL', $_btcpay_url);
        Configuration::updateValue('btcpay_LABEL', $label);
        Configuration::updateValue('btcpay_PUB', (string) $this->bitpay_encrypt($pub));
        Configuration::updateValue('btcpay_SIN', (string) $sin);
        Configuration::updateValue('btcpay_TOKEN', (string) $this->bitpay_encrypt($token));
        Configuration::updateValue('btcpay_KEY', (string) $this->bitpay_encrypt($key));

        return null;
    }

    /**
     * @param Cart $cart
     */
    public function execPayment($cart)
    {
        // Get shopping currency, currently tested with be EUR
        $currency = Currency::getCurrencyInstance((int) $cart->id_currency);
        if (true === empty($currency)) {
            return;
        }

        $transaction_speed = Configuration::get('btcpay_TXSPEED');
        if (true === empty($transaction_speed)) {
            $transaction_speed = 'default';
        }

        // get the cart id to fetch cart information
        $cart_id = $cart->id;
        if (true === empty($cart_id)) {
            $this->_errors[] = $this->l('[Error] The BTCPay payment plugin was called to process a payment but the cart_id was missing.');

            return;
        }

        // This is the callback url for invoice paid
        $notification_url = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/ipn.php';

        // Get a BitPay Client to prepare for invoice creation
        $client = new Client();
        if (false === isset($client) && true === empty($client)) {
            $this->_errors[] = $this->l('[Error] The BTCPay payment plugin was called to process a payment but could not instantiate a client object.');
        }

        $serverurl_btcpay = Configuration::get('btcpay_URL');
        $client->setUri($serverurl_btcpay);

        $curlAdapter = new CurlAdapter();
        if (false === isset($curlAdapter) || true === empty($curlAdapter)) {
            $this->_errors[] = $this->l('[Error] The BTCPay payment plugin was called to process a payment but could not instantiate a CurlAdapter object.');

            return;
        }

        $client->setAdapter($curlAdapter);

        $encrypted_key_btcpay = Configuration::get('btcpay_KEY');
        $key_btcpay = (string) $this->bitpay_decrypt($encrypted_key_btcpay);
        if (true === empty($key_btcpay)) {
            $this->_errors[] = $this->l('[Error] The BTCPay payment plugin was called to process a payment but could not set client->setPrivateKey to this->api_key. The empty() check failed!');

            return;
        }

        $key = new PrivateKey();
        $key->setHex($key_btcpay);
        $client->setPrivateKey($key);

        $pub_btcpay = $key->getPublicKey();
        $client->setPublicKey($pub_btcpay);

        $token_btcpay = (string) $this->bitpay_decrypt(Configuration::get('btcpay_TOKEN'));
        if (false === empty($token_btcpay)) {
            $_token = new Token();
            $_token->setToken($token_btcpay);
            $client->setToken($_token);
        } else {
            PrestaShopLogger::addLog('[Error] The BTCPay payment plugin was called to process a payment but could not set client->setToken to this->api_token. The empty() check failed!', 3);

            return;
        }

        // handle case order id already exist
        // Setup the Invoice
        $invoice = new Invoice();

        if (false === isset($invoice) || true === empty($invoice)) {
            PrestaShopLogger::addLog('[Error] The BTCPay payment plugin was called to process a payment but could not instantiate an Invoice object.', 3);
        }

        $btcpay_currency = new BitpayCurrency($currency->iso_code);
        $invoice->setOrderId((string) $cart_id);
        $invoice->setCurrency($btcpay_currency);
        $invoice->setFullNotifications(true);
        $invoice->setExtendedNotifications(true);

        // Add a priced item to the invoice
        $item = new Item();
        if (false === isset($item) || true === empty($item)) {
            $this->_errors[] = $this->l('[Error] The BTCPay payment plugin was called to process a payment but could not instantiate an item object.');
        }

        $customer = new Customer((int) $cart->id_customer);
        $email = $customer->email;

        $item = new Item();
        $item
            ->setCode('skuNumber')
            ->setDescription('Your purchase');

        // Get total value
        $cart_total = $cart->getOrderTotal(true);

        if (true === isset($cart_total) && false === empty($cart_total)) {
            $item->setPrice($cart_total);
        } else {
            $this->_errors[] = $this->l('[Error] The BTCPay payment plugin was called to process a payment but could not set item->setPrice to $order->getTotalPaid(). The empty() check failed!');
        }

        // Add the item
        $invoice->setItem($item);

        // Set POS data so we can verify later on that the call is legit
        $secure_key = $this->context->customer->secure_key;
        $invoice->setPosData($secure_key);

        // Add buyer's email to the invoice
        $buyer = new Buyer();
        $buyer->setEmail($email);
        $invoice->setBuyer($buyer);

        $invoice_reference = Tools::passwdGen(20);
        $redirect_url = Context::getContext()->link->getModuleLink('btcpay', 'validation', ['invoice_reference' => $invoice_reference]);

        // Add the Redirect and Notification URLs
        $invoice->setRedirectUrl($redirect_url);
        $invoice->setNotificationUrl($notification_url);
        $invoice->setTransactionSpeed($transaction_speed);

        // If another BTCPay invoice was created before, returns the original one
        $redirect = $this->get_btcpay_redirect($cart_id, $client);
        if ($redirect) {
            PrestaShopLogger::addLog('Existing BTCPay invoice has already been created, redirecting to it...' . $invoice->getId(), 2);

            header('Location:  ' . $redirect);
            exit(0);
        }

        //Ask BTCPay to create an invoice with cart information
        try {
            $invoice = $client->createInvoice($invoice);
            if (false === isset($invoice) || true === empty($invoice)) {
                PrestaShopLogger::addLog('[Error] The BTCPay payment plugin was called to process a payment but could not instantiate an invoice object.', 3);
            }

            PrestaShopLogger::addLog('Invoice ' . $invoice->getId() . ' created, see ' . $invoice->getUrl(), 2);

            // Register invoice into order_bitcoin table
            $this->writeCart($cart_id);
            $this->update_order_field($cart_id, 'invoice_id', $invoice->getId());

            // Set invoice reference
            $this->update_order_field($cart_id, 'invoice_reference', $invoice_reference);

            $responseData = json_decode($client->getResponse()->getBody(), false);

            // register invoice url and rate into order_bitcoin table
            $this->update_order_field($cart_id, 'amount', $cart_total);
            $this->update_order_field($cart_id, 'rate', $invoice->getRate());
            $this->update_order_field($cart_id, 'redirect', $invoice->getUrl());

            $this->update_btcpay($cart_id, $responseData);

            PrestaShopLogger::addLog('BTCPay invoice assigned ' . $invoice->getId(), 2);

            header('Location:  ' . $invoice->getUrl());
            exit(0);
        } catch (Exception $e) {
            $this->_errors[] = $this->l('Sorry, but Bitcoin checkout with BTCPay does not appear to be working.');
            exit(1);
        }

        exit(1);
    }

    // operations on btcpay plugin table
    private function get_order_field($cart_id, $order_field)
    {
        $db = Db::getInstance();
        $result = $db->ExecuteS('SELECT `' . $order_field . '` FROM `' . _DB_PREFIX_ . 'order_bitcoin` WHERE `cart_id`=' . (int) $cart_id . ';');
        if (count($result) > 0 && $result[0] !== null && $result[0][$order_field] !== null) {
            return $result[0][$order_field];
        }

        return null;
    }

    private function update_order_field($cart_id, $order_field, $order_value)
    {
        $db = Db::getInstance();
        $query = 'UPDATE `' . _DB_PREFIX_ . 'order_bitcoin` SET `' . $order_field . "`='" . $order_value . "' WHERE `cart_id`=" . (int) $cart_id . ';';

        return $db->Execute($query);
    }

    public function update_btcpay($cart_id, $responseData)
    {
        $this->update_order_field($cart_id, 'btc_price', $responseData->data->btcPrice);
        $this->update_order_field($cart_id, 'btc_paid', $responseData->data->btcPaid);
        $this->update_order_field($cart_id, 'btc_address', $responseData->data->bitcoinAddress);
    }

    public function get_btcpay_redirect($cart_id, $client)
    {
        $redirect = $this->get_order_field($cart_id, 'redirect');
        if (isset($redirect) && !empty($redirect)) {
            $result_invoice_id = $this->get_order_field($cart_id, 'invoice_id');
            $invoice = $client->getInvoice($result_invoice_id);
            $status = $invoice->getStatus();
            if ($status === 'invalid' || $status === 'expired') {
                $redirect = null;
            }
        }

        return $redirect;
    }

    public function writeCart($cart_id)
    {
        //39 want to pay in bitcoin
        $status = 39;
        $db = Db::getInstance();
        //39 want to pay in bitcoin
        PrestaShopLogger::addLog('Create order_bitcoin with cartid => ' . $cart_id);
        $result = $db->Execute('INSERT INTO `' . _DB_PREFIX_ . 'order_bitcoin` (`cart_id`, `status`) VALUES(' . (int) $cart_id . ', "' . $status . '") on duplicate key update `status`="' . $status . '"');
    }

    public function writeDetails($id_order, $cart_id, $status)
    {
        $status = stripslashes(str_replace("'", '', $status));
        $db = Db::getInstance();
        $result = $db->Execute('INSERT INTO `' . _DB_PREFIX_ . 'order_bitcoin` (`id_order`, `cart_id`, `status`) VALUES(' . (int) $id_order . ', ' . (int) $cart_id . ', "' . $status . '") on duplicate key update `status`="' . $status . '"');
    }

    public function readBitcoinPaymentDetails($id_order)
    {
        $db = Db::getInstance();
        $result = $db->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'order_bitcoin` WHERE `id_order` = ' . (int) $id_order . ';');
        if (count($result) > 0) {
            return $result[0];
        }

        return ['invoice_id' => 0, 'status' => 'null'];
    }

    // Hooks on prestashop payments
    public function hookInvoice($params)
    {
        $order_id = $params['id_order'];

        $paymentDetails = $this->readBitcoinPaymentDetails($order_id);
        if ($paymentDetails['invoice_id'] === 0) {
            return null;
        }

        $cart = Cart::getCartByOrderId($order_id);
        $currency = Currency::getCurrencyInstance((int) $cart->id_currency);

        $this->context->smarty->assign([
            'btcpayurl' => $this->btcpayurl,
            'currency_sign' => $currency->sign,
            'payment_details' => $paymentDetails,
        ]);

        return $this->display(__FILE__, 'invoice_block.tpl');
    }

    // Hooks on prestashop payment returns
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return null;
        }

        $order = $params['order'];
        $order_to_display = (new OrderPresenter())->present($order);

        $this->context->smarty->assign([
            'order' => $order_to_display,
        ]);

        return $this->display(__FILE__, 'payment_return.tpl');
    }

    //-- KEY MANAGEMENT
    public function bitpay_encrypt($data)
    {
        if (false === isset($data) || true === empty($data)) {
            $this->_errors[] = $this->l('The BTCPay payment plugin was called to encrypt data but no data was passed!');

            return null;
        }

        $openssl_ext = new OpenSSLExtension();
        $fingerprint = sha1(sha1(__DIR__));

        if (true === isset($fingerprint) &&
            true === isset($openssl_ext) &&
            strlen($fingerprint) > 24) {
            $fingerprint = substr($fingerprint, 0, 24);

            if (false === isset($fingerprint) || true === empty($fingerprint)) {
                $this->_errors[] = $this->l('The BTCPay payment plugin was called to encrypt data but could not generate a fingerprint parameter!');
            }

            $encrypted = $openssl_ext->encrypt(base64_encode(serialize($data)), $fingerprint, '1234567890123456');

            if (true === empty($encrypted)) {
                $this->_errors[] = $this->l('The BTCPay payment plugin was called to serialize an encrypted object and failed!');
            }

            return $encrypted;
        }

        die(Tools::displayError('Error: Invalid server fingerprint generated!'));
    }

    public function bitpay_decrypt($encrypted)
    {
        if (false === isset($encrypted) || true === empty($encrypted)) {
            $this->_errors[] = $this->l('The BTCPay payment plugin was called to decrypt data but no data was passed!');

            return null;
        }

        $openssl_ext = new OpenSSLExtension();
        $fingerprint = sha1(sha1(__DIR__));

        if (true === isset($fingerprint) &&
            true === isset($openssl_ext) &&
            strlen($fingerprint) > 24) {
            $fingerprint = substr($fingerprint, 0, 24);

            if (false === isset($fingerprint) || true === empty($fingerprint)) {
                $this->_errors[] = $this->l('The BTCPay payment plugin was called to decrypt data but could not generate a fingerprint parameter!');
            }

            $decrypted = base64_decode($openssl_ext->decrypt($encrypted, $fingerprint, '1234567890123456'));

            // Strict base64 char check
            if (false === base64_decode($decrypted, true)) {
                $this->_errors[] = $this->l('In bitpay_decrypt: data appears to have already been decrypted. Strict base64 check failed.');
            } else {
                $decrypted = base64_decode($decrypted);
            }

            if (true === empty($decrypted)) {
                $this->_errors[] = $this->l('The BTCPay payment plugin was called to unserialize a decrypted object and failed! The decrypt function was called with "' . $encrypted . '"');
            }

            return unserialize($decrypted);
        }

        die(Tools::displayError('Error: Invalid server fingerprint generated!'));
    }
}
