<?php

use BTCPay\Constants;
use BTCPay\Github\Versioning;
use BTCPay\Installer\Config;
use BTCPay\Installer\Hooks;
use BTCPay\Installer\OrderStates;
use BTCPay\Installer\Tables;
use BTCPay\Installer\Webhook;
use BTCPay\Repository\BitcoinPaymentRepository;
use BTCPay\Repository\TableRepository;
use BTCPay\Server\Client;
use BTCPayServer\Exception\BTCPayException;
use BTCPayServer\Exception\RequestException;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderPresenter;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
	exit;
}

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	throw new RuntimeException('Missing autoload');
}

require_once __DIR__ . '/vendor/autoload.php';

/** @noinspection AutoloadingIssuesInspection */
class BTCPay extends PaymentModule
{
	public $tabs = [
		[
			'name'              => 'BTCPay Server',
			'visible'           => true,
			'class_name'        => 'AdminConfigureBTCPay',
			'parent_class_name' => 'AdminParentPayment',
		],
	];

	/**
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * @var Versioning
	 */
	private $versioning;

	public function __construct()
	{
		$this->name                   = 'btcpay';
		$this->tab                    = 'payments_gateways';
		$this->version                = '6.1.2';
		$this->author                 = 'BTCPay Server';
		$this->ps_versions_compliancy = ['min' => Constants::MINIMUM_PS_VERSION, 'max' => _PS_VERSION_];
		$this->controllers            = ['payment', 'validation', 'webhook'];
		$this->is_eu_compatible       = true;
		$this->bootstrap              = true;
		$this->module_key             = 'aec3ab8084e8e626bdcbda75d2ab39ee';

		$this->currencies      = true;
		$this->currencies_mode = 'radio';

		parent::__construct();

		$this->displayName      = $this->trans('BTCPay Server', [], 'Modules.Btcpay.Admin');
		$this->description      = $this->trans('Accept crypto payments via BTCPay Server.', [], 'Modules.Btcpay.Front');
		$this->confirmUninstall = $this->trans('Are you sure that you want to uninstall this module? Past purchases and order states will be kept, but your configuration will be removed.', [], 'Modules.Btcpay.Front');

		$this->configuration = new Configuration();
		$this->versioning    = new Versioning();

		// Process any and all alerts/warnings
		$this->displayModuleWarnings();
	}

	public function install(): bool
	{
		if (!parent::install()) {
			return false;
		}

		if (version_compare(\PHP_VERSION, Constants::MINIMUM_PHP_VERSION, '<')) {
			$this->addModuleErrors([
				[
					'key'        => sprintf('PHP version is too low. Expected %s or higher, received %s.', Constants::MINIMUM_PHP_VERSION, \PHP_VERSION),
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				],
			]);

			return false;
		}

		if (version_compare(_PS_VERSION_, Constants::MINIMUM_PS_VERSION, '<')) {
			$this->addModuleErrors([
				[
					'key'        => sprintf('PrestaShop version is too low. Expected %s or higher, received %s.', Constants::MINIMUM_PS_VERSION, _PS_VERSION_),
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				],
			]);

			return false;
		}

		if (!empty($errors = (new Hooks($this))->install())) {
			$this->addModuleErrors($errors);

			return false;
		}

		if (!empty($errors = (new Config())->install())) {
			$this->addModuleErrors($errors);

			return false;
		}

		// Create required tables
		$repository = new TableRepository($this->get('doctrine.dbal.default_connection'));
		if (!empty($errors = (new Tables($repository))->install())) {
			$this->addModuleErrors($errors);
			$this->uninstall();

			return false;
		}

		if (!empty($errors = (new OrderStates($this->name))->install())) {
			$this->addModuleErrors($errors);
			$this->uninstall();

			return false;
		}

		return true;
	}

	/**
	 * When uninstalling, only remove the configuration and webhook. This way, order states and payment links are not lost.
	 */
	public function uninstall(): bool
	{
		if (!empty($errors = (new Webhook())->uninstall())) {
			$this->addModuleErrors($errors);
		}

		if (!empty($errors = (new Config())->uninstall())) {
			$this->addModuleErrors($errors);

			return false;
		}

		return parent::uninstall();
	}

	/**
	 * When resetting, only deal with the configuration. This way, order states and payment links are not lost.
	 */
	public function reset(): bool
	{
		$config = new Config();
		if (!empty($errors = $config->uninstall())) {
			$this->addModuleErrors($errors);

			return false;
		}

		if (!empty($errors = $config->install())) {
			$this->addModuleErrors($errors);

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

	/**
	 * Hooks on prestashop invoice
	 *
	 * @throws PrestaShopDatabaseException
	 * @throws JsonException
	 */
	public function hookDisplayAdminOrderMainBottom($params): ?string
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
		if (empty($serverUrl = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_HOST))) {
			return null;
		}

		// Ensure the client is ready for use
		if (null === ($client = Client::createFromConfiguration($this->configuration)) || false === $client->isValid()) {
			return null;
		}

		// Get the order
		if (null === ($bitcoinPayment = BitcoinPaymentRepository::getOneByOrderID($params['id_order']))) {
			return null;
		}

		// Check if it has an invoice ID
		if (null === ($invoiceId = $bitcoinPayment->getInvoiceId()) || empty($invoiceId)) {
			return null;
		}

		// Get the cart
		if (false === ($cart = Cart::getCartByOrderId($bitcoinPayment->getOrderId()))) {
			return null;
		}

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Prepare smarty
		$this->context->smarty->assign([
			'server_url'    => $serverUrl,
			'storeCurrency' => Currency::getCurrencyInstance($cart->id_currency)->getSymbol(),
		]);

		try {
			// Get the invoice and its payments
			$invoice        = $client->invoice()->getInvoice($storeID, $invoiceId);
			$paymentMethods = $client->invoice()->getPaymentMethods($storeID, $invoiceId);

			// Has any payment been received
			$paymentReceived = array_reduce($paymentMethods, static function ($carry, $method) {
				return empty($method->getPayments()) ? $carry : true;
			}, false);

			// Add more information to smarty
			$this->context->smarty->assign([
				'invoice'         => $invoice,
				'paymentMethods'  => $paymentMethods,
				'paymentReceived' => $paymentReceived,
			]);

			return $this->display(__FILE__, 'views/templates/admin/invoice_block.tpl');
		} catch (RequestException $exception) {
			// Log the exception
			PrestaShopLogger::addLog(\sprintf('[WARNING] Tried to load BTCPay Server invoice in hookDisplayAdminOrderMainBottom: %s', $exception->getMessage()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, $exception->getCode(), 'Order', $bitcoinPayment->getOrderId());

			// Show that the invoice was not found
			return $this->display(__FILE__, 'views/templates/admin/invoice_missing_block.tpl');
		}
	}

	/**
	 * Hooks on prestashop order details in frontend
	 *
	 * @throws JsonException
	 * @throws PrestaShopDatabaseException
	 */
	public function hookDisplayOrderDetail(array $params): ?string
	{
		// If the module is not active, abort
		if (!$this->active) {
			return null;
		}

		// Check if we have an order and cart, if not abort
		if (!array_key_exists('order', $params) || !array_key_exists('cart', $params)) {
			return null;
		}

		// Check if we actually have an order
		$order = $params['order'];
		if (!$order instanceof Order) {
			return null;
		}

		// If created by another module, return
		if ($order->module !== $this->name) {
			return false;
		}

		// Check if we actually have an cart
		$cart = $params['cart'];
		if (!$cart instanceof Cart) {
			return null;
		}

		// Get BTCPay URL or abort
		if (empty($serverUrl = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_HOST))) {
			return null;
		}

		// Ensure the client is ready for use
		if (null === ($client = Client::createFromConfiguration($this->configuration)) || false === $client->isValid()) {
			return null;
		}

		// Get the order
		if (null === ($bitcoinPayment = BitcoinPaymentRepository::getOneByOrderID($order->id))) {
			return null;
		}

		// Check if it has an invoice ID
		if (null === ($invoiceId = $bitcoinPayment->getInvoiceId()) || empty($invoiceId)) {
			return null;
		}

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		try {
			// Prepare smarty
			$this->context->smarty->assign([
				'serverURL'      => $serverUrl,
				'storeCurrency'  => Currency::getCurrencyInstance($cart->id_currency)->getSymbol(),
				'invoice'        => $client->invoice()->getInvoice($storeID, $invoiceId),
				'paymentMethods' => $client->invoice()->getPaymentMethods($storeID, $invoiceId),
			]);

			return $this->display(__FILE__, 'views/templates/hooks/order_detail.tpl');
		} catch (RequestException $exception) {
			// Log the exception
			PrestaShopLogger::addLog(\sprintf('[WARNING] Tried to load BTCPay Server invoice in hookDisplayOrderDetail: %s', $exception->getMessage()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, $exception->getCode(), 'Order', $order->id);

			// If the invoice is gone just return null
			return null;
		}
	}

	/**
	 * Hooks on prestashop payment returns
	 *
	 * @throws Exception
	 */
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

		// Get the order
		if (null === ($bitcoinPayment = BitcoinPaymentRepository::getOneByOrderID($order->id))) {
			return null;
		}

		// Prepare smarty to present order details
		$this->context->smarty->assign([
			'presenter'      => (new OrderPresenter())->present($order),
			'bitcoinPayment' => $bitcoinPayment,
			'order_state'    => $order->getCurrentState(),
			'os_waiting'     => $this->configuration->getInt(Constants::CONFIGURATION_ORDER_STATE_WAITING),
			'os_confirming'  => $this->configuration->getInt(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING),
			'os_failed'      => $this->configuration->getInt(Constants::CONFIGURATION_ORDER_STATE_FAILED),
			'os_paid'        => $this->configuration->getInt(Constants::CONFIGURATION_ORDER_STATE_PAID),
		]);

		return $this->display(__FILE__, 'views/templates/hooks/payment_return.tpl');
	}

	/**
	 * Hooks on prestashop payment options
	 *
	 * @throws SmartyException
	 * @throws JsonException
	 */
	public function hookPaymentOptions(): array
	{
		// If the module is not active, abort
		if (!$this->active) {
			return [];
		}

		// Ensure the client is ready for use
		if (null === ($client = Client::createFromConfiguration($this->configuration)) || false === $client->isValid()) {
			return [];
		}

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		try {
			// If the server is not fully synced, do not show the option
			if (!$client->server()->getInfo()->isFullySynced()) {
				return [];
			}

			// Prepare smarty
			$this->context->smarty->assign([
				'onChain'  => $client->onChain()->getPaymentMethods($storeID),
				'offChain' => $client->offChain()->getPaymentMethods($storeID),
			]);

			return [
				(new PaymentOption())
					->setModuleName($this->name)
					->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/images/payment.png'))
					->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true))
					->setCallToActionText($this->trans('Pay with BTCPay Server', [], 'Modules.Btcpay.Front'))
					->setAdditionalInformation($this->context->smarty->fetch('module:btcpay/views/templates/hooks/payment_option.tpl')),
			];
		} catch (BTCPayException $exception) {
			// Log the exception
			PrestaShopLogger::addLog(\sprintf('[WARNING] Could not load payments options from server: %s', $exception->getMessage()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, $exception->getCode());

			return [];
		}
	}

	/**
	 * Hooks on prestashop cart changes
	 *
	 * @throws JsonException
	 * @throws PrestaShopDatabaseException
	 * @throws PrestaShopException
	 */
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
		if (null === ($bitcoinPayment = BitcoinPaymentRepository::getOneByCartID($cart->id))) {
			return;
		}

		PrestaShopLogger::addLog('[INFO] Order has changed for cart. Cancelling....', \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, null, 'Cart', $cart->id);

		// Try to remove the order
		if (false === $bitcoinPayment->delete()) {
			$error = \sprintf('[ERROR] Expected to remove the order %s, but failed to do so', $bitcoinPayment->getOrderId());
			PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'BitcoinPayment', $bitcoinPayment->getId());

			throw new \PrestaShopDatabaseException($error);
		}
	}

	private function addModuleErrors(array $errors): void
	{
		foreach ($errors as $error) {
			$this->_errors[] = $this->trans($error['key'], $error['parameters'], $error['domain']);
		}
	}

	private function displayModuleWarnings(): void
	{
		// Try and create the client
		$client = Client::createFromConfiguration($this->configuration);

		// Show warning if API key is missing or if we're not yet fully synced
		if (null === $client || empty($this->configuration->get(Constants::CONFIGURATION_BTCPAY_API_KEY))) {
			$this->warning = $this->trans('Your BTCPay Server store has not yet been linked, payment option is unavailable.', [], 'Modules.Btcpay.Admin');
		} elseif (!$client->server()->getInfo()->isFullySynced()) {
			$this->warning = $this->trans('One (or more) coins are not yet synced, payment option will be unavailable until the sync has finished.', [], 'Modules.Btcpay.Admin');
		}

		// API key/sync warnings are more important than a new version, if a warning is set, return now
		if (!empty($this->warning)) {
			return;
		}

		// Otherwise, add a warning on version upgrade
		if (null !== ($latest = $this->versioning->latest()) && $latest->newer($this->version)) {
			$this->warning = $this->trans(\sprintf('There is a new version available for this plugin: %s.', $latest->getTagName()), [], 'Modules.Btcpay.Admin');
		}
	}
}
