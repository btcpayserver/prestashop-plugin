<?php

namespace BTCPay\Invoice;

if (!\defined('_PS_VERSION_')) {
	exit;
}

/**
 * Shared checkout and webhook input guards.
 */
class CheckoutGuard
{
	public static function isInvoiceReference(mixed $value): bool
	{
		return \is_string($value) && 1 === \preg_match('/^[A-Za-z0-9]{20}$/', $value);
	}

	public static function amountsMatch(string $a, string $b): bool
	{
		return 0 === \bccomp($a, $b, 8);
	}

	public static function isNonNegativeAmount(string $value): bool
	{
		// Strict whole-string anchors: "$" alone would accept a trailing newline,
		// which BCMath rejects with a ValueError
		return 1 === \preg_match('/\A\d+(\.\d+)?\z/', $value);
	}

	public static function isPositiveAmount(string $value): bool
	{
		// Exact check: a validated non-negative decimal is positive when it contains
		// any non-zero digit, no matter how many fractional digits it carries
		return self::isNonNegativeAmount($value) && 1 === \preg_match('/[1-9]/', $value);
	}

	public static function canCreateOrder(bool $partial, bool $processing, bool $paidLate, bool $overpaid, bool $settled): bool
	{
		return $partial || $processing || $paidLate || $overpaid || $settled;
	}

	public static function paidTransitionAllowed(bool $protect, bool $orderAlreadyPaid, bool $targetIsPaid): bool
	{
		return !$protect || !$orderAlreadyPaid || $targetIsPaid;
	}
}
