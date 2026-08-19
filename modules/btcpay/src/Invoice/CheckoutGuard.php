<?php

namespace BTCPay\Invoice;

if (!\defined('_PS_VERSION_')) {
	exit;
}

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

	public static function canCreateOrder(bool $partial, bool $processing, bool $paidLate, bool $overpaid, bool $settled): bool
	{
		return $partial || $processing || $paidLate || $overpaid || $settled;
	}

	public static function paidTransitionAllowed(bool $protect, bool $orderAlreadyPaid, bool $targetIsPaid): bool
	{
		return !$protect || !$orderAlreadyPaid || $targetIsPaid;
	}
}
