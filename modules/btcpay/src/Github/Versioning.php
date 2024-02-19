<?php

namespace BTCPay\Github;

use BTCPay\Constants;
use Github\Api\Repo;
use Github\Client;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class Versioning
{
	/**
	 * @var AdapterInterface
	 */
	private $cache;

	/**
	 * @var Repo
	 */
	private $client;

	public function __construct()
	{
		$this->cache = new FilesystemAdapter();

		$client = new Client();
		$client->addCache($this->cache);

		$this->client = new Repo($client);
	}

	public function latest(): ?Latest
	{
		// Check if we have a recent check, cached
		$cachedUpdate = $this->cache->getItem(Constants::LASTEST_VERSION_CACHE_KEY);
		if ($cachedUpdate->isHit() && !empty($cachedData = $cachedUpdate->get())) {
			return Latest::create($cachedData);
		}

		// Fetch the latest version
		$data = $this->client->releases()->latest('btcpayserver', 'prestashop-plugin');

		// If the data is empty, return null
		if (empty($data)) {
			return null;
		}

		// Set updated data
		$cachedUpdate->expiresAfter(Constants::LASTEST_VERSION_CACHE_EXPIRATION);
		$cachedUpdate->set($data);

		// Save updated data
		$this->cache->save($cachedUpdate);

		// Finally, return the data
		return Latest::create($cachedUpdate->get());
	}
}
