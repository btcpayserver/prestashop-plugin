<?php

namespace BTCPay\Server;

use BTCPay\Constants;
use BTCPayServer\Exception\ConnectException;
use BTCPayServer\Http\CurlClient;
use BTCPayServer\Http\ResponseInterface;
use STS\Backoff\Backoff;

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
	 * @var bool
	 */
	private $trackHealth;

	/**
	 * @param bool $trackHealth whether connection failures pause further BTCPay Server calls (false for GitHub)
	 */
	public function __construct(bool $trackHealth = true)
	{
		$this->trackHealth = $trackHealth;

		// Short, bounded waits between attempts (max 1 second in total)
		$this->backoff = new Backoff(self::$defaultMaxAttempts, Backoff::$defaultStrategy, 1000, true);

		// Retry only quick connection errors; a timeout is never retried (it would double the wait)
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

		// Never let a slow or unreachable BTCPay Server block the shop
		$this->setCurlOptions([
			\CURLOPT_CONNECTTIMEOUT => Constants::HTTP_CONNECT_TIMEOUT,
			\CURLOPT_TIMEOUT        => Constants::HTTP_TIMEOUT,
			\CURLOPT_NOSIGNAL       => 1,
		]);
	}

	/**
	 * Wrapped request using Backoff.
	 *
	 * - every request has a connection timeout and a total timeout (see Constants::HTTP_*);
	 * - only GET requests are retried (creating an invoice twice must never happen);
	 * - while BTCPay Server is marked unreachable, requests fail immediately without touching the network;
	 * - a connection failure marks BTCPay Server unreachable for a short window. The configuration is never changed.
	 *
	 * @throws \Exception
	 */
	public function request(string $method, string $url, array $headers = [], string $body = ''): ResponseInterface
	{
		if ($this->trackHealth && Health::isUnreachable()) {
			throw new ConnectException('BTCPay Server marked unreachable, request skipped', 0);
		}

		$send = function () use ($method, $url, $headers, $body) {
			return parent::request($method, $url, $headers, $body);
		};

		try {
			$response = 'GET' === \strtoupper($method) ? $this->backoff->run($send) : $send();
		} catch (ConnectException $exception) {
			if ($this->trackHealth) {
				Health::markUnreachable($exception->getMessage());
			}

			throw $exception;
		}

		if ($this->trackHealth && $response->getStatus() < 500) {
			Health::markReachable();
		}

		return $response;
	}
}
