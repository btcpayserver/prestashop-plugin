<?php

namespace BTCPay\Github;

use BTCPay\Constants;
use BTCPay\Server\CurlAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class Versioning
{
	private const HEADERS = [
		'Accept'       => 'application/json',
		'Content-Type' => 'application/json',
		'User-Agent'   => 'btcpayserver/prestashop-plugin',
	];

	/**
	 * @var FilesystemAdapter
	 */
	private $cache;

	/**
	 * @var CurlAdapter
	 */
	private $client;

	public function __construct()
	{
		$this->cache  = new FilesystemAdapter();
		$this->client = new CurlAdapter(false);
	}

	public function latest(): ?Latest
	{
		try {
			// Check if we have a recent check cached (a failed check is cached too, as an empty array)
			$cachedUpdate = $this->cache->getItem(Constants::LASTEST_VERSION_CACHE_KEY);
			if ($cachedUpdate->isHit()) {
				$cachedData = $cachedUpdate->get();

				return \is_array($cachedData) && !empty($cachedData) ? Latest::create($cachedData) : null;
			}

			// Fetch the latest version
			$response = $this->client->request(Request::METHOD_GET, Constants::GITHUB_API_LATEST_ENDPOINT, self::HEADERS);

			// If the request failed, stop bothering
			if (200 !== $response->getStatus()) {
				\PrestaShopLogger::addLog(\sprintf('[WARNING] Could not check for latest version, received status: %s', $response->getStatus()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, $response->getStatus());
				$this->rememberFailure($cachedUpdate);

				return null;
			}

			// Decode JSON response
			$data = \json_decode($response->getBody(), true, 512, \JSON_THROW_ON_ERROR);

			// If the data is empty (or the request failed), return null
			if (empty($data) || false === \array_key_exists('tag_name', $data) || (\array_key_exists('message', $data) && 'Not Found' === $data['message'])) {
				$this->rememberFailure($cachedUpdate);

				return null;
			}

			// Set updated data
			$cachedUpdate->expiresAfter(Constants::LASTEST_VERSION_CACHE_EXPIRATION);
			$cachedUpdate->set($data);

			// Save updated data
			$this->cache->save($cachedUpdate);

			// Finally, return the data
			return Latest::create($cachedUpdate->get());
		} catch (\Throwable $exception) {
			\PrestaShopLogger::addLog(\sprintf('[INFO] Could not check for latest version, caught exception: %s', $exception->getMessage()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, $exception->getCode());

			if (isset($cachedUpdate)) {
				$this->rememberFailure($cachedUpdate);
			}

			return null;
		}
	}

	/**
	 * Without internet the check fails: remember it for a day instead of retrying (and logging) on every page.
	 *
	 * @param \Symfony\Contracts\Cache\ItemInterface|\Psr\Cache\CacheItemInterface $item
	 */
	private function rememberFailure($item): void
	{
		try {
			$item->expiresAfter(Constants::LASTEST_VERSION_FAILURE_EXPIRATION);
			$item->set([]);
			$this->cache->save($item);
		} catch (\Throwable $exception) {
			// Cache not writable: nothing else to do
		}
	}
}
