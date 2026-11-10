<?php

namespace BTCPay\Tests\Invoice;

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
}
