<?php

class BTCpayValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $cart = $this->context->cart;
        if ($cart->id_customer === 0 || $cart->id_address_delivery === 0 || $cart->id_address_invoice === 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] === $this->module->name) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        // Get the passed invoice reference, which we can then use to get the actual order
        $invoice_reference = Tools::getValue('invoice_reference', 0);
        $cart_id = (int) $this->get_order_field_by_reference($invoice_reference, 'cart_id');

        // Check if the cart has been made by a guest customer
        $is_guest = Cart::isGuestCartByCartId($cart_id);

        // Set basic redirect URL
        $redirectLink = $is_guest
            ? 'index.php?controller=guest-tracking'
            : 'index.php?controller=history';

        // If this module is fucked, just redirect away
        if (!$this->module->id) {
            Tools::redirect($redirectLink);
        }

        // Get the order and validate it
        $order = Order::getByCartId($cart_id);
        if ($order === null || $order->id === 0 || (int) $order->id_customer !== (int) $this->context->customer->id) {
            Tools::redirect($redirectLink);
        }

        // Get the customer so we can do a fancy redirect
        $customer = new Customer((int) $cart->id_customer);
        if ($is_guest) {
            Tools::redirect($redirectLink . '&order_reference=' . $order->reference . '&email=' . urlencode($customer->email));

            return;
        }

        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $order->id_cart . '&id_module=' . $this->module->id . '&id_order=' . $order->id . '&key=' . $customer->secure_key);
    }

    private function get_order_field_by_reference($invoice_reference, $order_field)
    {
        $db = Db::getInstance();
        $query = 'SELECT `' . $order_field . '` FROM `' . _DB_PREFIX_ . "order_bitcoin` WHERE `invoice_reference`='" . $invoice_reference . "';";
        $result = $db->ExecuteS($query);

        if (count($result) > 0 && $result[0] !== null && $result[0][$order_field] !== null) {
            return $result[0][$order_field];
        }

        return null;
    }
}
