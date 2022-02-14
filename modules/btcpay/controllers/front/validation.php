<?php

use BTCPay\LegacyBitcoinPaymentRepository;

class BTCPayValidationModuleFrontController extends ModuleFrontController
{
	/**
	 * Enable SSL only.
	 *
	 * @var bool
	 */
	public $ssl = true;

	/**
	 * @var LegacyBitcoinPaymentRepository
	 */
	private $repository;

	public function __construct()
	{
		parent::__construct();

		$this->repository = new LegacyBitcoinPaymentRepository();
	}

	/**
	 * @throws PrestaShopDatabaseException
	 * @throws JsonException
	 */
	public function postProcess(): void
	{
		// Check if the cart we have is even valid
		$cart = $this->context->cart;
		if (0 === $cart->id_customer || 0 === $cart->id_address_delivery || 0 === $cart->id_address_invoice || !$this->module->active) {
			Tools::redirect($this->context->link->getPageLink('order', $this->ssl, null, ['step' => 1]));

			return;
		}

		// Get the translator so we can translate our errors
		if (null === ($translator = $this->getTranslator())) {
			throw new \RuntimeException('Expected the translator to be available');
		}

		// Check if our payment option is still valid
		$authorized = false;
		foreach (Module::getPaymentModules() as $module) {
			if ($module['name'] === $this->module->name) {
				$authorized = true;

				break;
			}
		}

		// If it's no longer valid, redirect the customer.
		if (!$authorized) {
			$this->warning[] = $translator->trans('This payment method is not available.', [], 'Modules.Btcpay.Front');
			$this->redirectWithNotifications($this->context->link->getPageLink('cart', $this->ssl));

			return;
		}

		// Get the passed invoice reference, which we can then use to get the actual order
		$invoiceReference = Tools::getValue('invoice_reference', 0);
		if (null === ($bitcoinPayment = $this->repository->getOneByInvoiceReference($invoiceReference))) {
			$this->warning[] = $translator->trans('There is no order that we can validate.', [], 'Modules.Btcpay.Front');
			$this->redirectWithNotifications($this->context->link->getPageLink('cart', $this->ssl));

			return;
		}

		// Get the order and validate it
		$order = Order::getByCartId($bitcoinPayment->getCartId());
		if (null === $order || 0 === $order->id || (int) $order->id_customer !== (int) $this->context->customer->id) {
			$this->warning[] = $translator->trans('There is no order that we can process.', [], 'Modules.Btcpay.Front');
			$this->redirectWithNotifications($this->context->link->getPageLink('cart', $this->ssl));

			return;
		}

		// Get the customer so we can do a fancy redirect
		$customer = new Customer((int) $cart->id_customer);

		// If it's a guest, sent them to guest tracking
		if (Cart::isGuestCartByCartId($bitcoinPayment->getCartId())) {
			Tools::redirect($this->context->link->getPageLink('guest-tracking', $this->ssl, null, ['order_reference' => $order->reference, 'email' => $customer->email]));

			return;
		}

		// If it's an actual customer, sent them to the order confirmation page
		Tools::redirect($this->context->link->getPageLink('order-confirmation', $this->ssl, null, [
			'id_cart'   => $order->id_cart,
			'id_module' => $this->module->id,
			'id_order'  => $order->id,
			'key'       => $customer->secure_key,
		]));
	}
}
