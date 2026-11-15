<?php

declare(strict_types=1);

namespace BTCPay\Invoice;

use BTCPay;
use BTCPay\Constants;
use BTCPay\Exception\OrderCreationBusyException;
use BTCPay\Repository\BitcoinPaymentRepository;
use BTCPay\Repository\OrderInvoicePaymentLinker;
use BTCPay\Repository\OrderPaymentRepository;
use BTCPay\Server\Client;
use Cart;
use Context;
use Customer;
use Doctrine\DBAL\Connection;
use Module;
use Order;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;
use RuntimeException;
use Tools;
use Validate;

if (!\defined('_PS_VERSION_')) {
	exit;
}

final class PaymentReturn
{
	public const REDIRECT = 'redirect';
	public const NOTIFY = 'notify';
	public const TEMPLATE = 'template';

	public function __construct(
		private readonly BTCPay $module,
		private readonly ShopConfigurationInterface $configuration,
		private readonly BitcoinPaymentRepository $payments,
		private readonly Connection $connection,
		private readonly OrderPaymentRepository $orderPayments,
		private readonly OrderInvoicePaymentLinker $invoicePayments,
	) {
	}

	/**
	 * @return array{kind: string, target: string, warning: ?string}
	 */
	public function handle(): array
	{
		// Check if the cart we have is even valid
		$context = Context::getContext();
		$cart = $context->cart;
		if (0 === (int) $cart->id_customer || 0 === (int) $cart->id_address_delivery || 0 === (int) $cart->id_address_invoice || !$this->module->active) {
			$parameters = [];
			// Classic checkout still has steps. One Page Checkout does not.
			if (!(bool) $this->configuration->get('PS_ONE_PAGE_CHECKOUT_ENABLED')) {
				$parameters['step'] = 1;
			}

			return ['kind' => self::REDIRECT, 'target' => $context->link->getPageLink('order', true, null, $parameters), 'warning' => null];
		}

		// Get the translator so we can translate our errors
		$translator = $context->getTranslator();
		if (null === $translator) {
			throw new RuntimeException('Expected the translator to be available');
		}

		// Check if our payment option is still valid
		$paymentAvailable = false;
		foreach (Module::getPaymentModules() as $module) {
			if ($module['name'] === $this->module->name) {
				$paymentAvailable = true;

				break;
			}
		}

		// If it's no longer valid, redirect the customer.
		if (!$paymentAvailable) {
			return ['kind' => self::NOTIFY, 'target' => $context->link->getPageLink('cart', true), 'warning' => $translator->trans('This payment method is not available.', [], 'Modules.Btcpay.Front')];
		}

		// Get the passed invoice reference, which we can then use to get the actual order
		$invoiceReference = Tools::getValue('invoice_reference');
		if (!CheckoutGuard::isInvoiceReference($invoiceReference) || null === ($bitcoinPayment = $this->payments->findOneByInvoiceReference((string) $invoiceReference))) {
			return ['kind' => self::NOTIFY, 'target' => $context->link->getPageLink('cart', true), 'warning' => $translator->trans('The passed invoice reference is not valid.', [], 'Modules.Btcpay.Front')];
		}

		// Load the payment's cart and bind the return request to that customer and shop
		$paymentCart = new Cart($bitcoinPayment->getCartId());
		if (
			!Validate::isLoadedObject($paymentCart)
			|| (int) $paymentCart->id_customer !== (int) $context->customer->id
			|| (int) $paymentCart->id_shop !== (int) $context->shop->id
		) {
			return ['kind' => self::NOTIFY, 'target' => $context->link->getPageLink('cart', true), 'warning' => $translator->trans('The passed invoice reference is not valid.', [], 'Modules.Btcpay.Front')];
		}

		// Grab the current order mode
		$orderMode = $this->configuration->get(Constants::CONFIGURATION_ORDER_MODE);

		// Get the order and validate it
		$order = Order::getByCartId($bitcoinPayment->getCartId());
		if (null !== $order && 0 !== (int) $order->id) {
			if ((int) $order->id_customer !== (int) $context->customer->id) {
				return ['kind' => self::NOTIFY, 'target' => $context->link->getPageLink('cart', true), 'warning' => $translator->trans('The passed invoice reference is not valid.', [], 'Modules.Btcpay.Front')];
			}
		} elseif (Constants::ORDER_MODE_BEFORE === $orderMode) {
			// The order must exist when using the `before` order mode
			return ['kind' => self::NOTIFY, 'target' => $context->link->getPageLink('cart', true), 'warning' => $translator->trans('There is no order that we can process.', [], 'Modules.Btcpay.Front')];
		} else {
			// Ensure the client is ready for use
			if (null === ($client = Client::createFromConfiguration($this->configuration)) || false === $client->isValid()) {
				throw new RuntimeException('Expected the client to be available');
			}

			// User was quicker than the callback, so we deal with the actual invoice now.
			try {
				(new Processor($this->module, $context, $this->configuration, $client, $this->payments, $this->connection, $this->orderPayments, $this->invoicePayments))
					->paymentReceivedCreateAfter($bitcoinPayment);
			} catch (OrderCreationBusyException) {
				// Reloading stays on this URL. A cart redirect would save the cart and delete the payment row.
				return ['kind' => self::TEMPLATE, 'target' => 'module:btcpay/views/templates/front/order-pending.tpl', 'warning' => null];
			}

			// Grab the (now existing) order
			$order = Order::getByCartId($bitcoinPayment->getCartId());
		}

		if (null === $order || 0 === (int) $order->id) {
			// Do not send the shopper to order confirmation until payment created an order
			return ['kind' => self::NOTIFY, 'target' => $context->link->getPageLink('cart', true), 'warning' => $translator->trans('We have not received a payment yet.', [], 'Modules.Btcpay.Front')];
		}

		// Get the customer so we can do a fancy redirect
		$customer = new Customer((int) $paymentCart->id_customer);

		// If it's a guest, sent them to guest tracking
		if (Cart::isGuestCartByCartId($bitcoinPayment->getCartId())) {
			return ['kind' => self::REDIRECT, 'target' => $context->link->getPageLink('guest-tracking', true, null, ['order_reference' => $order->reference, 'email' => $customer->email]), 'warning' => null];
		}

		// If it's an actual customer, sent them to the order confirmation page
		return ['kind' => self::REDIRECT, 'target' => $context->link->getPageLink('order-confirmation', true, null, [
			'id_cart' => $order->id_cart,
			'id_module' => $this->module->id,
			'id_order' => $order->id,
			'key' => $customer->secure_key,
		]), 'warning' => null];
	}
}
