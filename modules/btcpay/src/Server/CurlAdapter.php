<?php

namespace BTCPay\Server;

use BTCPayServer\Client\Adapter\CurlAdapter as BaseCurlAdapter;
use BTCPayServer\Client\RequestInterface;
use BTCPayServer\Client\ResponseInterface;
use STS\Backoff\Backoff;

class CurlAdapter extends BaseCurlAdapter
{
	/**
	 * @var int
	 */
	public static $defaultMaxAttempts = 3;

	/**
	 * @var Backoff
	 */
	private $backoff;

	public function __construct(array $curlOptions = [])
	{
		parent::__construct($curlOptions);

		$this->backoff = new Backoff(self::$defaultMaxAttempts, Backoff::$defaultStrategy, self::$defaultMaxAttempts * 10 * 1000, true);
	}

	/**
	 * Wrapped request using Backoff.
	 *
	 * Four things can happen:
	 * 1. This call succeeds and we return like normal
	 * 2. The call fails and we retry 3 times using the default strategy, if any retry succeeds, we return it
	 * 3. The call fails and we retry 3 times using the default strategy, if all retries fail, it will throw the last error
	 * 4. The maximum waiting time exceeds and we will just throw the last error
	 *
	 * @return ResponseInterface
	 * @throws \Exception
	 */
	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		return $this->backoff->run(function () use ($request) {
			return parent::sendRequest($request);
		});
	}
}
