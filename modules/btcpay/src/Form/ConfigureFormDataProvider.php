<?php

namespace BTCPay\Form;

use BTCPay\Form\Data\Configuration;
use BTCPay\Server\Client;
use BTCPayServer\PrivateKey;
use BTCPayServer\PublicKey;
use BTCPayServer\SinKey;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

class ConfigureFormDataProvider implements FormDataProviderInterface
{
	/**
	 * @return Configuration[]
	 */
	public function getData(): array
	{
		// Get BTCPay URL or use sane default
		$serverUrl = \Configuration::get('BTCPAY_URL');
		if (true === empty($serverUrl)) {
			$serverUrl = 'https://testnet.demo.btcpayserver.org';
		}

		$configuration = new Configuration(
			$serverUrl,
			\Configuration::get('BTCPAY_TXSPEED'),
			\Configuration::get('BTCPAY_ORDERMODE'),
			\Configuration::get('BTCPAY_PAIRINGCODE')
		);

		return ['btcpay' => $configuration];
	}

	public function setData(array $data): array
	{
		/** @var Configuration $configuration */
		$configuration = $data['btcpay'];

		\Configuration::updateValue('BTCPAY_URL', rtrim($configuration->getUrl(), '/\\'));
		\Configuration::updateValue('BTCPAY_TXSPEED', $configuration->getTransactionSpeed());
		\Configuration::updateValue('BTCPAY_ORDERMODE', $configuration->getOrderMode());

		// Only update the pairing code if it's different
		if (\Configuration::get('BTCPAY_PAIRINGCODE') === $configuration->getPairingCode()) {
			return [];
		}

		// Try and update the pairing code
		if (!empty($errors = $this->processPairingCode($configuration->getUrl(), $configuration->getPairingCode()))) {
			return $errors;
		}

		\Configuration::updateValue('BTCPAY_PAIRINGCODE', $configuration->getPairingCode());

		return [];
	}

	private function processPairingCode(string $serverUrl, string $pairing_code): ?array
	{
		// Generate Private Key for api security
		$privateKey = new PrivateKey();
		$privateKey->generate();

		// Generate Public Key
		$publicKey = new PublicKey();
		$publicKey->setPrivateKey($privateKey);
		$publicKey->generate();

		// Get SIN Key
		$sinKey = new SinKey();
		$sinKey->setPublicKey($publicKey);
		$sinKey->generate();

		// Create an API Client
		$client = new Client();
		$client->setUri(Client::getURI($serverUrl));
		$client->setPrivateKey($privateKey);
		$client->setPublicKey($publicKey);

		$label = \Configuration::get('PS_SHOP_NAME');

		try {
			// Ignore notices and warnings for now
			$old_error_reporting = error_reporting();
			error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_WARNING);

			$token = $client->createToken(
				[
					'id'          => (string) $sinKey,
					'pairingCode' => $pairing_code,
					'label'       => $label,
				]
			);

			error_reporting($old_error_reporting);
		} catch (\Exception $e) {
			\PrestaShopLogger::addLog('[ERROR] Issue creating token:' . $e->getMessage(), 3);

			return [
				[
					'key'        => sprintf('Failed to create token: %s', $e->getMessage()),
					'domain'     => 'Admin.Catalog.Notification',
					'parameters' => [],
				],
			];
		}

		if (false === isset($token)) {
			return [
				[
					'key'        => 'Failed to create token, you are maybe using an already activated pairing code.',
					'domain'     => 'Admin.Catalog.Notification',
					'parameters' => [],
				],
			];
		}

		// Encrypt or return errors
		if (\is_array($pubEncrypted = $client->getEncryption()->encrypt($publicKey))) {
			return $pubEncrypted;
		}

		// Encrypt or return errors
		if (\is_array($tokenEncrypted = $client->getEncryption()->encrypt($token))) {
			return $tokenEncrypted;
		}

		// Encrypt or return errors
		if (\is_array($keyEncrypted = $client->getEncryption()->encrypt($privateKey))) {
			return $keyEncrypted;
		}

		// Update our configuration with the new data
		\Configuration::updateValue('BTCPAY_LABEL', $label);
		\Configuration::updateValue('BTCPAY_PUB', (string) $pubEncrypted);
		\Configuration::updateValue('BTCPAY_SIN', (string) $sinKey);
		\Configuration::updateValue('BTCPAY_TOKEN', (string) $tokenEncrypted);
		\Configuration::updateValue('BTCPAY_KEY', (string) $keyEncrypted);

		return null;
	}
}
