<?php

namespace BTCPay\Invoice;

if (!\defined('_PS_VERSION_')) {
	exit;
}

/**
 * Shop-currency amounts for settled payments, rounded so they sum to one total.
 */
class PaymentAmounts
{
	/**
	 * Round new amounts so already booked plus new equals round(sum of all raws).
	 *
	 * @param list<string> $newRawAmounts
	 * @param list<string> $allRawAmounts
	 *
	 * @return list<string>
	 */
	public static function allocateRoundedAmounts(
		array $newRawAmounts,
		int $precision,
		array $allRawAmounts,
		string $alreadyBooked
	): array {
		if ([] === $newRawAmounts) {
			return [];
		}

		// Aggregation scale derives from the converted amounts' fractional lengths, so
		// summing them stays lossless; rounding to the booking precision happens on top
		$workScale = $precision;
		foreach ($allRawAmounts as $raw) {
			$workScale = \max($workScale, CheckoutGuard::fractionalDigits((string) $raw));
		}
		$sum       = '0';
		foreach ($allRawAmounts as $raw) {
			$sum = \bcadd($sum, (string) $raw, $workScale);
		}

		$target = \bcsub(self::roundDecimal($sum, $precision), $alreadyBooked, $precision);
		$last   = \array_key_last($newRawAmounts);
		$result = [];
		$used   = '0';

		foreach ($newRawAmounts as $index => $raw) {
			if ($index === $last) {
				$result[] = \bcsub($target, $used, $precision);

				break;
			}

			$part     = self::roundDecimal((string) $raw, $precision);
			$result[] = $part;
			$used     = \bcadd($used, $part, $precision);
		}

		return $result;
	}

	private static function roundDecimal(string $amount, int $precision): string
	{
		// Multiply exactly (no truncation), then apply the half-up correction before
		// the final truncate
		$mulScale = \max(CheckoutGuard::fractionalDigits($amount), $precision);
		$factor   = \bcpow('10', (string) $precision, 0);

		return \bcdiv(\bcadd(\bcmul($amount, $factor, $mulScale), '0.5', 0), $factor, $precision);
	}
}
