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

	/**
	 * @var ShopConfigurationInterface
	 */
	private $configuration;

	public function __construct(ShopConfigurationInterface $configuration)
	{
		$this->configuration = $configuration;
	}

	public function issue(): string
	{
		return $this->store('authorize', \time() + self::LIFETIME);
	}

	/**
	 * @param list<array{type: string, message: string}> $messages
	 */
	public function complete(array $messages, int $expires): string
	{
		return $this->store('complete', $expires, $messages);
	}

	public function consume(string $token, string $purpose = 'authorize'): ?array
	{
		if (1 !== \preg_match('/\A[a-f0-9]{64}\z/', $token)) {
			return null;
		}

		$stored = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_AUTH_CALLBACK);
		$data   = \is_string($stored) ? \json_decode($stored, true) : null;
		if (!\is_array($data) || !isset($data['hash'], $data['expires'], $data['host'], $data['purpose'], $data['messages'])
			|| $data['purpose'] !== $purpose || !\is_array($data['messages'])
			|| !\is_string($data['hash']) || !\is_int($data['expires'])
			|| $data['expires'] <= \time() || $data['expires'] > \time() + self::LIFETIME
			|| $data['host'] !== $this->configuration->get(Constants::CONFIGURATION_BTCPAY_HOST)
			|| !\hash_equals($data['hash'], \hash('sha256', $token))) {
			return null;
		}

		// Clear only the matching pending token so concurrent callbacks cannot both succeed.
		$db  = \Db::getInstance();
		$sql = \sprintf(
			"UPDATE `%sconfiguration` SET `value` = '' WHERE `name` = '%s' AND BINARY `value` = BINARY '%s'",
			\_DB_PREFIX_,
			\pSQL(Constants::CONFIGURATION_BTCPAY_AUTH_CALLBACK),
			\pSQL($stored)
		);

		if (!$db->execute($sql) || 1 !== (int) $db->Affected_Rows()) {
			return null;
		}

		return $data;
	}

	/**
	 * @param list<array{type: string, message: string}> $messages
	 */
	private function store(string $purpose, int $expires, array $messages = []): string
	{
		$token = \bin2hex(\random_bytes(32));
		$this->configuration->set(Constants::CONFIGURATION_BTCPAY_AUTH_CALLBACK, \json_encode([
			'hash'     => \hash('sha256', $token),
			'expires'  => $expires,
			'purpose'  => $purpose,
			'messages' => $messages,
			'host'     => $this->configuration->get(Constants::CONFIGURATION_BTCPAY_HOST),
		], \JSON_THROW_ON_ERROR));

		return $token;
	}
}
