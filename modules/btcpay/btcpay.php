<?php

use BTCPay\LegacyOrderBitcoinRepository;
use BTCPay\Repository\BitcoinPaymentRepository;
use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderPresenter;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
	exit;
}

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	throw new RuntimeException('Missing autoload');
}

require_once __DIR__ . '/vendor/autoload.php';

class BTCPay extends PaymentModule
{
	/**
	 * @var BitcoinPaymentRepository
	 */
	private $repository;

	public $tabs = [
		[
			'name'              => 'BTCPay',
			'visible'           => true,
			'class_name'        => 'AdminConfigureBTCPay',
			'parent_class_name' => 'AdminParentPayment',
		],
	];

	public function __construct()
	{
		$this->name                   = 'btcpay';
		$this->tab                    = 'payments_gateways';
		$this->version                = '3.0.0';
		$this->author                 = 'BTCPayServer';
		$this->ps_versions_compliancy = ['min' => '1.7.6', 'max' => _PS_VERSION_];
		$this->controllers            = ['payment', 'validation', 'ipn'];
		$this->is_eu_compatible       = true;
		$this->bootstrap              = true;

		$this->currencies      = true;
		$this->currencies_mode = 'radio';

		parent::__construct();

		$this->displayName      = $this->trans('BTCPay', [], 'Modules.Btcpay.Admin');
		$this->description      = $this->trans('Accepts Bitcoin payments via BTCPay.', [], 'Modules.Btcpay.Front');
		$this->confirmUninstall = $this->trans('Are you sure you want to delete your details?', [], 'Modules.Btcpay.Front');
	}

	public function install(): bool
	{
		if (!parent::install()) {
			return false;
		}

		if (null === ($repository = $this->getRepository())) {
			return false;
		}

		if (!$this->registerHook('displayInvoice')
			|| !$this->registerHook('displayAdminOrderTop')
			|| !$this->registerHook('displayOrderDetail')
			|| !$this->registerHook('displayPaymentEU')
			|| !$this->registerHook('payment')
			|| !$this->registerHook('paymentReturn')
			|| !$this->registerHook('paymentOptions')
			|| !$this->registerHook('actionCartSave')) {
			$this->uninstall();

			return false;
		}

		// Init clear configurations
		if (!Configuration::updateValue('BTCPAY_URL', null)
			|| !Configuration::updateValue('BTCPAY_LABEL', null)
			|| !Configuration::updateValue('BTCPAY_PAIRINGCODE', null)
			|| !Configuration::updateValue('BTCPAY_KEY', null)
			|| !Configuration::updateValue('BTCPAY_PUB', null)
			|| !Configuration::updateValue('BTCPAY_SIN', null)
			|| !Configuration::updateValue('BTCPAY_TOKEN', null)
			|| !Configuration::updateValue('BTCPAY_TXSPEED', null)
			|| !Configuration::updateValue('BTCPAY_ORDERMODE', null)) {
			$this->uninstall();

			return false;
		}

		if (!empty($errors = $repository->createTables())) {
			$this->addModuleErrors($errors);
			$this->uninstall();

			return false;
		}

		if (!empty($errors = $repository->installOrderStates($this->name))) {
			$this->addModuleErrors($errors);
			$this->uninstall();

			return false;
		}

		return true;
	}

	public function uninstall(): bool
	{
		if (null === ($repository = $this->getRepository())) {
			return false;
		}

		// Remove configuration
		if (!Configuration::deleteByName('BTCPAY_URL')
			|| !Configuration::deleteByName('BTCPAY_LABEL')
			|| !Configuration::deleteByName('BTCPAY_PAIRINGCODE')
			|| !Configuration::deleteByName('BTCPAY_KEY')
			|| !Configuration::deleteByName('BTCPAY_PUB')
			|| !Configuration::deleteByName('BTCPAY_SIN')
			|| !Configuration::deleteByName('BTCPAY_TOKEN')
			|| !Configuration::deleteByName('BTCPAY_TXSPEED')
			|| !Configuration::deleteByName('BTCPAY_ORDERMODE')) {
			$this->addModuleErrors(
				[
					'key'        => 'Could not clear configuration',
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				]
			);

			return false;
		}

		if (!empty($errors = $repository->dropTables())) {
			$this->addModuleErrors($errors);

			return false;
		}

		if (!empty($errors = $this->uninstallOrderStates())) {
			$this->addModuleErrors($errors);

			return false;
		}

		return parent::uninstall();
	}

	public function uninstallOrderStates(): array
	{
		$collection = new PrestaShopCollection('OrderState');
		$collection->where('module_name', '=', $this->name);

		if (empty($orderStates = $collection->getResults())) {
			return [];
		}

		$errors = [];

		/** @var OrderState $orderState */
		foreach ($orderStates as $orderState) {
			if (false === $orderState->delete()) {
				$errors[] = [
					'key'        => 'Could not delete order state ' . $orderState->name,
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				];
			}
		}

		return $errors;
	}

	public function reset(): bool
	{
		if (!$this->uninstall()) {
			return false;
		}

		if (!$this->install()) {
			return false;
		}

		return true;
	}

	public function isUsingNewTranslationSystem(): bool
	{
		return true;
	}

	public function getContent(): void
	{
		Tools::redirectAdmin($this->context->link->getAdminLink('AdminConfigureBTCPay'));
	}

	public function hookDisplayInvoice($params): ?string
	{
		return $this->hookDisplayAdminOrderTop($params);
	}

	// Hooks on prestashop invoice
	public function hookDisplayAdminOrderTop($params): ?string
	{
		// If the module is not active, abort
		if (!$this->active) {
			return null;
		}

		// Check if we have an order available to use
		if (!array_key_exists('id_order', $params)) {
			return null;
		}

		// Get BTCPay URL or abort
		if (empty($serverUrl = Configuration::get('BTCPAY_URL'))) {
			return null;
		}

		// Get legacy repository
		$repository = new LegacyOrderBitcoinRepository();

		// Get the order
		if (null === ($orderBitcoin = $repository->getOneByOrderID($params['id_order']))) {
			return null;
		}

		// Check if it has an invoice ID
		if (null === ($invoiceId = $orderBitcoin->getInvoiceId()) || empty($invoiceId)) {
			return null;
		}

		// Get the cart
		if (false === ($cart = Cart::getCartByOrderId($orderBitcoin->getOrderId()))) {
			return null;
		}

		// Get the currency from our cart
		$currency = Currency::getCurrencyInstance((int) $cart->id_currency);

		// Prepare smarty
		$this->context->smarty->assign(
			[
				'server_url'      => $serverUrl,
				'currency_sign'   => $currency->sign,
				'payment_details' => $orderBitcoin->toArray(),
			]
		);

		return $this->display(__FILE__, 'views/templates/admin/invoice_block.tpl');
	}

	// Hooks on prestashop order details in frontend
	public function hookDisplayOrderDetail($params): ?string
	{
		// If the module is not active, abort
		if (!$this->active) {
			return null;
		}

		// Check if we have an order and cart, if not abort
		if (!array_key_exists('order', $params) || !array_key_exists('cart', $params)) {
			return null;
		}

		// Get BTCPay URL or abort
		if (empty($serverUrl = Configuration::get('BTCPAY_URL'))) {
			return null;
		}

		// Check if we actually have an order
		$order = $params['order'];
		if (!$order instanceof Order) {
			return null;
		}

		// Check if we actually have an order
		$cart = $params['cart'];
		if (!$cart instanceof Cart) {
			return null;
		}

		// Get legacy repository
		$repository = new LegacyOrderBitcoinRepository();

		// Get the order
		if (null === ($orderBitcoin = $repository->getOneByOrderID($order->id))) {
			return null;
		}

		// Check if it has an invoice ID
		if (null === ($invoiceId = $orderBitcoin->getInvoiceId()) || empty($invoiceId)) {
			return null;
		}

		// Get the currency from our cart
		$currency = Currency::getCurrencyInstance((int) $cart->id_currency);

		// Prepare smarty
		$this->context->smarty->assign(
			[
				'server_url'      => $serverUrl,
				'currency_sign'   => $currency->sign,
				'payment_details' => $orderBitcoin->toArray(),
			]
		);

		return $this->display(__FILE__, 'views/templates/hooks/order_detail.tpl');
	}

	public function hookDisplayPaymentEU(): array
	{
		if (!$this->active) {
			return [];
		}

		return [
			'cta_text' => $this->name,
			'logo'     => Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/images/bitcoin.png'),
			'action'   => $this->context->link->getModuleLink($this->name, 'payment', [], true),
		];
	}

	// Hooks on prestashop payment returns
	public function hookPaymentReturn($params): ?string
	{
		// If the module is not active, abort
		if (!$this->active) {
			return null;
		}

		// Check if we have an order, if not abort
		if (!array_key_exists('order', $params)) {
			return null;
		}

		// Check if we actually have an order
		$order = $params['order'];
		if (!$order instanceof Order) {
			return null;
		}

		// Prepare smarty to present order details
		$this->context->smarty->assign(
			[
				'presenter'   => (new OrderPresenter())->present($order),
				'order_state' => $order->getCurrentState(),
			]
		);

		return $this->display(__FILE__, 'views/templates/hooks/payment_return.tpl');
	}

	// Hooks on prestashop payment options
	public function hookPaymentOptions(): array
	{
		if (!$this->active) {
			return [];
		}

		$paymentOption = new PaymentOption();
		$paymentOption->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/images/bitcoin.png'));
		$paymentOption->setModuleName($this->name);
		$paymentOption->setCallToActionText($this->trans('Pay with Bitcoin', [], 'Modules.Btcpay.Front'));
		$paymentOption->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true));

		return [$paymentOption];
	}

	// Hooks on prestashop cart changes
	public function hookActionCartSave(array $params): void
	{
		// If the module is not active, abort
		if (!$this->active) {
			return;
		}

		// Check if we have a cart available or abort
		if (!array_key_exists('cart', $params)) {
			return;
		}

		/** @var Cart $cart */
		if (null === ($cart = $params['cart'])) {
			return;
		}

		// Check if we have a cart ID we can use
		if (empty($cart->id)) {
			return;
		}

		// Get the order
		if (null === ($orderBitcoin = (new LegacyOrderBitcoinRepository())->getOneByCartID($cart->id))) {
			return;
		}

		PrestaShopLogger::addLog('[WARNING] Order has changed for cart: ' . $cart->id . '. Cancelling....', 2);

		// Try to remove the order
		if (false === $orderBitcoin->delete()) {
			PrestaShopLogger::addLog('[ERROR] Expected to remove the order, but failed to do so', 3);
			throw new \PrestaShopDatabaseException('Expected to remove the order, but failed to do so');
		}
	}

	private function addModuleErrors(array $errors): void
	{
		foreach ($errors as $error) {
			$this->_errors[] = $this->trans($error['key'], $error['parameters'], $error['domain']);
		}
	}

	private function getRepository(): ?BitcoinPaymentRepository
	{
		if (null === $this->repository) {
			try {
				$this->repository = $this->get('prestashop.module.btcpay.repository');
			} catch (Throwable $e) {
				if (null !== ($container = SymfonyContainer::getInstance())) {
					$this->repository = new BitcoinPaymentRepository(
						$container->get('doctrine.dbal.default_connection'),
						$container->getParameter('database_prefix')
					);
				}
			}
		}

		return $this->repository;
	}
}
