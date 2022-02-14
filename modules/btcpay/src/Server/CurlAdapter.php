<?php

namespace BTCPay\Server;

use BTCPayServer\Http\CurlClient;
use BTCPayServer\Http\ResponseInterface;
use STS\Backoff\Backoff;

class CurlAdapter extends CurlClient
{
	/**
	 * @var int
	 */
	public static $defaultMaxAttempts = 3;

	/**
	 * @var Backoff
	 */
	private $backoff;

	public function __construct()
	{
		$this->backoff = new Backoff(self::$defaultMaxAttempts, Backoff::$defaultStrategy, self::$defaultMaxAttempts * 10 * 1000, true);
	}

	/**
	 * Wrapped request using Backoff.
	 *
	 * Four things can happen:
	 * 1. This call succeeds and we return like normal
	 * 2. The call fails, and we retry 3 times using the default strategy, if any retry succeeds, we return it
	 * 3. The call fails, and we retry 3 times using the default strategy, if all retries fail, it will throw the last error
	 * 4. The maximum waiting time exceeds, and we will just throw the last error
	 *
	 * @throws \Exception
	 */
	public function request(string $method, string $url, array $headers = [], string $body = ''): ResponseInterface
	{
		return $this->backoff->run(function () use ($method, $url, $headers, $body) {
			return parent::request($method, $url, $headers, $body);
		});
	}
}
