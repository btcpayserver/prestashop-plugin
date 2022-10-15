<?php

namespace BTCPay;

use BTCPayServer\Client\InvoiceCheckoutOptions;

class Constants
{
	// Version information
	public const MINIMUM_BTCPAY_VERSION = '1.3.0';
	public const MINIMUM_PS_VERSION     = '1.7.8';
	public const MINIMUM_PHP_VERSION    = '7.3.0';

	// Cache configuration
	public const LASTEST_VERSION_CACHE_KEY        = 'BTCPAY_LATEST_VERSION';
	public const LASTEST_VERSION_CACHE_EXPIRATION = 60 * 60 * 24 * 7; // 7 days

	// BTCPay Server webhook header
	public const BTCPAY_HEADER_SIG = 'Btcpay-Sig';

	// BTCPay required permissions
	public const BTCPAY_PERMISSIONS = [
		'btcpay.store.canmodifystoresettings',
		'btcpay.store.webhooks.canmodifywebhooks',
		'btcpay.store.canviewstoresettings',
		'btcpay.store.cancreateinvoice',
		'btcpay.store.canviewinvoices',
		'btcpay.store.canmodifyinvoices',
	];

	// BTCPay Server configuration
	public const CONFIGURATION_BTCPAY_HOST           = 'BTCPAY_URL';
	public const CONFIGURATION_BTCPAY_API_KEY        = 'BTCPAY_API_KEY';
	public const CONFIGURATION_BTCPAY_STORE_ID       = 'BTCPAY_STORE_ID';
	public const CONFIGURATION_BTCPAY_WEBHOOK_ID     = 'BTCPAY_WEBHOOK_ID';
	public const CONFIGURATION_BTCPAY_WEBHOOK_SECRET = 'BTCPAY_WEBHOOK_SECRET';

	// Default values
	public const CONFIGURATION_DEFAULT_HOST = 'https://testnet.demo.btcpayserver.org';

	// Order (creation) related configuration
	public const CONFIGURATION_ORDER_MODE = 'BTCPAY_ORDERMODE';

	// Order states
	public const CONFIGURATION_ORDER_STATE_WAITING    = 'BTCPAY_OS_WAITING';
	public const CONFIGURATION_ORDER_STATE_CONFIRMING = 'BTCPAY_OS_CONFIRMING';
	public const CONFIGURATION_ORDER_STATE_FAILED     = 'BTCPAY_OS_FAILED';
	public const CONFIGURATION_ORDER_STATE_PAID       = 'BTCPAY_OS_PAID';

	// Do we want to share personal details with BTCPay Server
	public const CONFIGURATION_SHARE_METADATA = 'BTCPAY_SHARE_METADATA';

	// All possible transaction speeds as defined by BTCPay server
	public const CONFIGURATION_SPEED_MODE = 'BTCPAY_TXSPEED';
	public const TRANSACTION_SPEEDS       = [InvoiceCheckoutOptions::SPEED_HIGH, InvoiceCheckoutOptions::SPEED_MEDIUM, InvoiceCheckoutOptions::SPEED_LOW];

	// All possible options for order creation (before or after payment)
	public const ORDER_MODES       = [self::ORDER_MODE_BEFORE, self::ORDER_MODE_AFTER];
	public const ORDER_MODE_BEFORE = 'before_payment';
	public const ORDER_MODE_AFTER  = 'after_payment';
}
