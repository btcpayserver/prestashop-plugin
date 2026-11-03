<?php

namespace BTCPay\Github;

use BTCPay\Constants;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Request;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class Versioning
{
	/**
	 * @var FilesystemAdapter
	 */
	private $cache;

	public function __construct()
	{
		$this->cache = new FilesystemAdapter();
	}

	/**
	 * Peek at the GitHub version cache without fetching.
	 *
	 * @return array{state: 'ok'|'failure'|'empty', remaining: ?int}
	 */
	public function cacheStatus(): array
	{
		$item = $this->cache->getItem(Constants::LASTEST_VERSION_CACHE_KEY);
		if (!$item->isHit()) {
			return ['state' => 'empty', 'remaining' => null];
		}

		$data      = $item->get();
		$expiresAt = $item->getMetadata()[CacheItem::METADATA_EXPIRY] ?? null;

		return [
			'state'     => \is_array($data) && !empty($data) ? 'ok' : 'failure',
			'remaining' => null !== $expiresAt ? \max(0, (int) \ceil($expiresAt - \microtime(true))) : null,
		];
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

			$context = \stream_context_create([
				'http' => [
					'method'        => Request::METHOD_GET,
					'header'        => "Accept: application/json\r\nContent-Type: application/json\r\nUser-Agent: btcpayserver/prestashop-plugin\r\n",
					'timeout'       => Constants::HTTP_TIMEOUT,
					'ignore_errors' => true,
				],
			]);

			$body = \Tools::file_get_contents(Constants::GITHUB_API_LATEST_ENDPOINT, false, $context, Constants::HTTP_TIMEOUT);
			if (empty($body)) {
				\PrestaShopLogger::addLog('[WARNING] Could not check for latest version, empty response from GitHub', \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING);
				$this->rememberFailure($cachedUpdate);

				return null;
			}

			// Decode JSON response
			$data = \json_decode($body, true, 512, \JSON_THROW_ON_ERROR);

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
	 * Without internet the check fails: remember it for a day instead of retrying on every page.
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
