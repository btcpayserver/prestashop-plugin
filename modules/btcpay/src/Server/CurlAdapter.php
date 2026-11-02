<?php

namespace BTCPay\Server;

use BTCPay\Constants;
use BTCPayServer\Exception\ConnectException;
use BTCPayServer\Http\CurlClient;
use BTCPayServer\Http\ResponseInterface;
use STS\Backoff\Backoff;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class CurlAdapter extends CurlClient
{
	/**
	 * @var int
	 */
	public static $defaultMaxAttempts = Constants::HTTP_MAX_ATTEMPTS;

	/**
	 * @var Backoff
	 */
	private $backoff;

	/**
	 * @var FilesystemAdapter|null
	 */
	private static $cache;

	public function __construct()
	{
		// Cap wait between retries at 1s (library default can grow much larger)
		$this->backoff = new Backoff(self::$defaultMaxAttempts, Backoff::$defaultStrategy, 1000, true);

		// Retry connection failures only. Curl errno 28 (CURLE_OPERATION_TIMEDOUT) means we already waited HTTP_TIMEOUT; retrying would stack delays on the shop.
		$this->backoff->setDecider(static function ($attempt, $maxAttempts, $result = null, $exception = null) {
			if (null === $exception) {
				return false;
			}

			$retryable = $exception instanceof ConnectException && 28 !== (int) $exception->getCode();
			if (!$retryable || $attempt >= $maxAttempts) {
				throw $exception;
			}

			return true;
		});

		$this->setCurlOptions([
			\CURLOPT_CONNECTTIMEOUT => Constants::HTTP_CONNECT_TIMEOUT,
			\CURLOPT_TIMEOUT        => Constants::HTTP_TIMEOUT,
			// Avoid SIGALRM-based timeouts (broken with PHP-FPM / multi-request)
			\CURLOPT_NOSIGNAL       => 1,
		]);
	}

	/**
	 * GET may be retried (idempotent). POST/PUT/DELETE run once, then pause remote calls on ConnectException.
	 *
	 * @throws \Exception
	 */
	public function request(string $method, string $url, array $headers = [], string $body = ''): ResponseInterface
	{
		if (self::isPaused()) {
			throw new ConnectException('BTCPay Server marked unreachable, request skipped', 0);
		}

		$send = function () use ($method, $url, $headers, $body) {
			return parent::request($method, $url, $headers, $body);
		};

		try {
			return Request::METHOD_GET === \strtoupper($method) ? $this->backoff->run($send) : $send();
		} catch (ConnectException $exception) {
			self::pause($exception->getMessage());

			throw $exception;
		}
	}

	/**
	 * Clear the unreachable pause (e.g. after a signed webhook proves BTCPay is back).
	 */
	public static function resume(): void
	{
		self::cache()->deleteItem(Constants::CACHE_UNREACHABLE);
	}

	private static function isPaused(): bool
	{
		return self::cache()->getItem(Constants::CACHE_UNREACHABLE)->isHit();
	}

	private static function pause(string $reason): void
	{
		if (self::isPaused()) {
			return;
		}

		\PrestaShopLogger::addLog(
			\sprintf('[WARNING] BTCPay Server unreachable, remote calls paused for %d seconds (configuration kept): %s', Constants::UNREACHABLE_WINDOW, $reason),
			\PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING
		);

		$item = self::cache()->getItem(Constants::CACHE_UNREACHABLE);
		$item->expiresAfter(Constants::UNREACHABLE_WINDOW);
		$item->set(true);
		self::cache()->save($item);
	}

	private static function cache(): FilesystemAdapter
	{
		return self::$cache ??= new FilesystemAdapter();
	}
}
