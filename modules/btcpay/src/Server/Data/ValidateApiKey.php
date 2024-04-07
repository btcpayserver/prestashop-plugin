<?php

namespace BTCPay\Server\Data;

use BTCPay\Constants;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Validator\Constraints as Assert;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class ValidateApiKey
{
	/**
	 * @Assert\NotBlank()
	 *
	 * @var string|null
	 */
	private $apiKey;

	/**
	 * @Assert\All({@Assert\NotBlank()})
	 * @Assert\NotBlank()
	 *
	 * @var string[]
	 */
	private $permissions;

	public function __construct(ParameterBag $request)
	{
		$this->apiKey      = $request->get('apiKey');
		$this->permissions = $request->get('permissions', []);
	}

	public function getApiKey(): ?string
	{
		return $this->apiKey;
	}

	public function getStoreID(): string
	{
		return \explode(':', $this->permissions[0])[1];
	}

	/**
	 * @Assert\IsTrue(message="This plugin expects all passed permissions to be given. Please remove and recreate the API key")
	 */
	public function hasRequiredPermissions(): bool
	{
		$permissions = \array_reduce($this->permissions, static function (array $carry, string $permission) {
			return \array_merge($carry, [\explode(':', $permission)[0]]);
		}, []);

		return empty(\array_merge(
			\array_diff(Constants::BTCPAY_PERMISSIONS, $permissions),
			\array_diff($permissions, Constants::BTCPAY_PERMISSIONS)
		));
	}

	/**
	 * @Assert\IsTrue(message="This plugin requires one store (and one store only) to be authorized. Please remove and recreate the API key.")
	 */
	public function hasSingleStore(): bool
	{
		$storeId = null;
		foreach ($this->permissions as $perms) {
			if (2 !== \count($exploded = \explode(':', $perms))) {
				return false;
			}

			if (null === ($receivedStoreId = $exploded[1])) {
				return false;
			}

			if ($storeId === $receivedStoreId) {
				continue;
			}

			if (null === $storeId) {
				$storeId = $receivedStoreId;

				continue;
			}

			return false;
		}

		return true;
	}
}
