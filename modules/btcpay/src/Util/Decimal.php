<?php

namespace BTCPay\Util;

if (!\defined('_PS_VERSION_')) {
	exit;
}

/**
 * Decimal string arithmetic used for amounts. Uses bcmath when available, otherwise an exact
 * pure-PHP implementation, so a missing bcmath extension can never cause a fatal error at payment time.
 */
class Decimal
{
	/**
	 * Compare two decimal strings at the given scale (like bccomp): -1, 0 or 1.
	 */
	public static function comp(string $a, string $b, int $scale = 8): int
	{
		if (self::hasBcmath()) {
			return \bccomp(self::normalize($a), self::normalize($b), $scale);
		}

		[$na, $ia, $fa] = self::split($a, $scale);
		[$nb, $ib, $fb] = self::split($b, $scale);
		$ia .= $fa;
		$ib .= $fb;

		// Treat -0 as 0
		if ('' === \ltrim($ia, '0')) {
			$na = false;
		}
		if ('' === \ltrim($ib, '0')) {
			$nb = false;
		}

		if ($na !== $nb) {
			return $na ? -1 : 1;
		}

		$cmp = self::compareDigits($ia, $ib);

		return $na ? -$cmp : $cmp;
	}

	/**
	 * Multiply two decimal strings, truncating to the given scale (like bcmul).
	 */
	public static function mul(string $a, string $b, int $scale = 8): string
	{
		if (self::hasBcmath()) {
			return \bcmul(self::normalize($a), self::normalize($b), $scale);
		}

		[$na, $ia, $fa] = self::split($a);
		[$nb, $ib, $fb] = self::split($b);
		$decimals = \strlen($fa) + \strlen($fb);
		$product  = self::multiplyDigits(\ltrim($ia . $fa, '0') ?: '0', \ltrim($ib . $fb, '0') ?: '0');
		$product  = \str_pad($product, $decimals + 1, '0', \STR_PAD_LEFT);
		$int      = \ltrim(\substr($product, 0, \strlen($product) - $decimals), '0') ?: '0';
		$frac     = \substr(\substr($product, \strlen($product) - $decimals) . \str_repeat('0', $scale), 0, $scale);
		$zero     = '0' === $int && '' === \trim($frac, '0');
		$sign     = ($na !== $nb) && !$zero ? '-' : '';

		return $sign . $int . ($scale > 0 ? '.' . $frac : '');
	}

	public static function hasBcmath(): bool
	{
		return \function_exists('bccomp') && \function_exists('bcmul') && !\defined('BTCPAY_FORCE_NO_BCMATH');
	}

	/**
	 * Accepts "12", "12.5", "-0.001", " 3.10 "; throws on anything else (no float notation, no thousands separator).
	 */
	public static function normalize(string $value): string
	{
		$value = \trim($value);
		if (1 !== \preg_match('/^[+-]?(\d+(\.\d*)?|\.\d+)$/', $value)) {
			throw new \InvalidArgumentException(\sprintf('Not a decimal number: "%s"', $value));
		}

		$value = \ltrim($value, '+');
		if ('.' === $value[0] || ('-' === $value[0] && '.' === $value[1])) {
			$value = \str_replace('.', '0.', $value);
		}

		return \rtrim($value, '.');
	}

	/**
	 * @return array{0: bool, 1: string, 2: string} [negative, integer digits, fraction digits (padded/truncated to $scale when given)]
	 */
	private static function split(string $value, ?int $scale = null): array
	{
		$value    = self::normalize($value);
		$negative = '-' === $value[0];
		$value    = \ltrim($value, '-');
		$parts    = \explode('.', $value, 2);
		$int      = \ltrim($parts[0], '0') ?: '0';
		$frac     = $parts[1] ?? '';
		if (null !== $scale) {
			$frac = \substr(\str_pad($frac, $scale, '0'), 0, $scale);
		}

		return [$negative, $int, $frac];
	}

	private static function compareDigits(string $a, string $b): int
	{
		$a = \ltrim($a, '0') ?: '0';
		$b = \ltrim($b, '0') ?: '0';
		if (\strlen($a) !== \strlen($b)) {
			return \strlen($a) < \strlen($b) ? -1 : 1;
		}

		return \max(-1, \min(1, \strcmp($a, $b)));
	}

	private static function multiplyDigits(string $a, string $b): string
	{
		$la     = \strlen($a);
		$lb     = \strlen($b);
		$result = \array_fill(0, $la + $lb, 0);
		for ($i = $la - 1; $i >= 0; --$i) {
			for ($j = $lb - 1; $j >= 0; --$j) {
				$sum = $result[$i + $j + 1] + ((int) $a[$i]) * ((int) $b[$j]);
				$result[$i + $j + 1] = $sum % 10;
				$result[$i + $j] += \intdiv($sum, 10);
			}
		}

		return \ltrim(\implode('', $result), '0') ?: '0';
	}
}
