<?php

namespace BTCPay\Server;

use BTCPay\Constants;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class AuthorizationToken
{
	private const LIFETIME = 900;

	private ShopConfigurationInterface $configuration;

	public function __construct(ShopConfigurationInterface $configuration)
	{
		$this->configuration = $configuration;
	}

	public function issue(): string
	{
		$token = \bin2hex(\random_bytes(32));
		$this->configuration->set(Constants::CONFIGURATION_BTCPAY_AUTH_CALLBACK, \json_encode([
			'hash'    => \hash('sha256', $token),
			'expires' => \time() + self::LIFETIME,
			'host'    => $this->configuration->get(Constants::CONFIGURATION_BTCPAY_HOST),
		], \JSON_THROW_ON_ERROR));

		return $token;
	}

	public function consume(string $token): bool
	{
		if (1 !== \preg_match('/\A[a-f0-9]{64}\z/', $token)) {
			return false;
		}

		$stored = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_AUTH_CALLBACK);
		$data = \is_string($stored) ? \json_decode($stored, true) : null;
		if (!\is_array($data) || !isset($data['hash'], $data['expires'], $data['host'])
			|| !\is_string($data['hash']) || !\is_int($data['expires'])
			|| $data['expires'] <= \time() || $data['expires'] > \time() + self::LIFETIME
			|| $data['host'] !== $this->configuration->get(Constants::CONFIGURATION_BTCPAY_HOST)
			|| !\hash_equals($data['hash'], \hash('sha256', $token))) {
			return false;
		}

		// Compare and clear in the database: concurrent callbacks must not both win.
		// The random hash uniquely identifies this attempt, even across shop scopes.
		$db = \Db::getInstance();
		$sql = \sprintf(
			"UPDATE `%sconfiguration` SET `value` = '' WHERE `name` = '%s' AND BINARY `value` = BINARY '%s'",
			\_DB_PREFIX_,
			\pSQL(Constants::CONFIGURATION_BTCPAY_AUTH_CALLBACK),
			\pSQL($stored)
		);

		return $db->execute($sql) && 1 === (int) $db->Affected_Rows();
	}
}
