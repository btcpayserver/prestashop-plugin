<?php

namespace BTCPay\Server;

if (!\defined('_PS_VERSION_')) {
	exit;
}

/**
 * Guards applied to incoming webhook requests before the body is processed.
 */
class WebhookRequestGuard
{
	/**
	 * BTCPay webhook payloads are small; anything larger is rejected early.
	 */
	public const MAX_BODY_BYTES = 1048576;

	public static function hasJsonContentType(?string $contentType): bool
	{
		if (null === $contentType) {
			return false;
		}

		$mediaType = \strtolower(\trim(\explode(';', $contentType, 2)[0]));

		return 'application/json' === $mediaType;
	}

	public static function isWithinBodyLimit(int $length): bool
	{
		return $length >= 0 && $length <= self::MAX_BODY_BYTES;
	}

	public static function isValidJson(string $body): bool
	{
		try {
			return \is_array(\json_decode($body, true, 512, \JSON_THROW_ON_ERROR));
		} catch (\JsonException) {
			return false;
		}
	}
}
