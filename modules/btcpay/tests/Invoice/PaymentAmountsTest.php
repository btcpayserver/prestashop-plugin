<?php

namespace BTCPay\Tests\Invoice;

use BTCPay\Invoice\CheckoutGuard;
use BTCPay\Invoice\PaymentAmounts;
use PHPUnit\Framework\TestCase;

class PaymentAmountsTest extends TestCase
{
	public function testAllocateRoundedAmountsSumEqualsRoundOfSum(): void
	{
		$this->assertSame([], PaymentAmounts::allocateRoundedAmounts([], 2, [], '0'));
		$this->assertSame(['19.12'], PaymentAmounts::allocateRoundedAmounts(['19.12'], 2, ['19.12'], '0'));

		$allocated = PaymentAmounts::allocateRoundedAmounts(['10.004', '10.004'], 2, ['10.004', '10.004'], '0');
		$this->assertSame(['10.00', '10.01'], $allocated);
		$this->assertSame('20.01', \bcadd($allocated[0], $allocated[1], 2));
	}

	public function testAllocateRoundedAmountsClosesSequentialResidue(): void
	{
		$second = PaymentAmounts::allocateRoundedAmounts(['10.004'], 2, ['10.004', '10.004'], '10.00');
		$this->assertSame(['10.01'], $second);
	}

	public function testAllocateRoundedAmountsAccumulatesTinyFractions(): void
	{
		// Three payments whose fractions only become significant when accumulated
		$allocated = PaymentAmounts::allocateRoundedAmounts(
			['0.004', '0.004', '0.004'],
			2,
			['0.004', '0.004', '0.004'],
			'0'
		);

		$this->assertSame(['0.00', '0.00', '0.01'], $allocated);
		$this->assertSame('0.01', \bcadd(\bcadd($allocated[0], $allocated[1], 2), $allocated[2], 2));
	}

	public function testAllocateRoundedAmountsKeepsHighPrecisionAggregation(): void
	{
		// Sub-cent payments that only round up after accumulation, at a currency with
		// more decimals than the default two
		$allocated = PaymentAmounts::allocateRoundedAmounts(
			['0.000000005', '0.000000005'],
			8,
			['0.000000005', '0.000000005'],
			'0'
		);

		$this->assertSame(['0.00000001', '0.00000000'], $allocated);
	}

	public function testAllocateRoundedAmountsAggregatesLosslessly(): void
	{
		// Regression (allocation only): exact raws of the reviewer reproduction —
		// 4.9999999999999999 x 0.001 and 0.0000000000000001 x 0.001, order precision
		// 2. The exact sum 0.005 must round up to 0.01. A fixed working scale
		// truncated the sequential accumulation below the half-up boundary (the
		// first intermediate lost the 19th fractional digit), so the target became
		// 0.00 and the payments booked as 0.00 in total.
		$raws = ['0.0049999999999999999', '0.0000000000000000001'];

		$allocated = PaymentAmounts::allocateRoundedAmounts($raws, 2, $raws, '0');

		$this->assertSame(['0.00', '0.01'], $allocated);
	}

	public function testConvertThenAllocateBooksExactTotal(): void
	{
		// Regression (full processor pipeline): a 16-decimal payment value times a
		// rate that adds fractional digits. A fixed conversion scale truncated the
		// first product to 0.004999999999999999 and dropped the second as zero.
		$first  = CheckoutGuard::convertAmount('4.9999999999999999', '0.001');
		$second = CheckoutGuard::convertAmount('0.0000000000000001', '0.001');

		$this->assertSame('0.0049999999999999999', $first);
		$this->assertSame('0.0000000000000000001', $second);

		$allocated = PaymentAmounts::allocateRoundedAmounts([$first, $second], 2, [$first, $second], '0');

		$this->assertSame('0.01', \bcadd($allocated[0], $allocated[1], 2));
	}
}
