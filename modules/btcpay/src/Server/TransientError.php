<?php

namespace BTCPay\Server;

use BTCPayServer\Exception\ConnectException;
use BTCPayServer\Exception\RequestException;

if (!\defined('_PS_VERSION_')) {
	exit;
}

/**
 * Classifies BTCPay Server client failures that are temporary in nature.
 *
 * A temporary failure (server restarting, a reverse proxy returning 502/503/504,
 * rate limiting) is no evidence that the stored credentials are wrong, so the
 * module must keep the merchant's configuration instead of resetting it.
 */
class TransientError
{
	/**
	 * HTTP statuses that indicate a temporary condition rather than an invalid configuration.
	 * 408: request timeout, 429: rate limited.
	 */
	private const TRANSIENT_HTTP_STATUSES = [408, 429];

	/**
	 * @param \Throwable $exception the exception thrown while talking to BTCPay Server
	 *
	 * @return bool true if the failure is temporary and the configuration should be kept
	 */
	public static function isTransient(\Throwable $exception): bool
	{
		// Connection-level failures (curl errors, timeouts, unreachable pause) are always temporary
		if ($exception instanceof ConnectException) {
			return true;
		}

		// RequestException carries the HTTP status as its code. BadRequestException (400) and
		// ForbiddenException (403) extend it too, but prove the stored credentials are invalid.
		if (!$exception instanceof RequestException) {
			return false;
		}

		// Gateway-level failures (e.g. 502/503/504 from a proxy in front of a restarting server)
		if ($exception->getCode() >= 500) {
			return true;
		}

		// 408: request timeout, 429: rate limited
		return \in_array($exception->getCode(), self::TRANSIENT_HTTP_STATUSES, true);
	}
}
