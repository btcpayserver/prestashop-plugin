<?php

namespace BTCPay\Installer;

use Configuration;

class Config
{
	public function install(): array
	{
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
			return [
				[
					'key'        => 'Could not init configuration',
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				],
			];
		}

		return [];
	}

	public function uninstall(): array
	{
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
			return [
				[
					'key'        => 'Could not clear configuration',
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				],
			];
		}

		return [];
	}
}
