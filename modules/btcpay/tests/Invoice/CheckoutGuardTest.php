<?php

namespace BTCPay\Tests\Invoice;

use BTCPay\Invoice\CheckoutGuard;
use PHPUnit\Framework\TestCase;

class CheckoutGuardTest extends TestCase
{
	public function testInvoiceReferenceMustBeTwentyAlphanumericCharacters(): void
	{
		$this->assertTrue(CheckoutGuard::isInvoiceReference('ABCDEFGHIJ0123456789'));
		$this->assertFalse(CheckoutGuard::isInvoiceReference('ABCDEFGHIJ012345678'));
		$this->assertFalse(CheckoutGuard::isInvoiceReference('ABCDEFGHIJ01234567890'));
		$this->assertFalse(CheckoutGuard::isInvoiceReference('ABCDEFGHIJ-123456789'));
		$this->assertFalse(CheckoutGuard::isInvoiceReference(123));
		$this->assertFalse(CheckoutGuard::isInvoiceReference(null));
	}

	public function testAmountsMatchUsesNumericComparison(): void
	{
		$this->assertTrue(CheckoutGuard::amountsMatch('10.00', '10'));
		$this->assertTrue(CheckoutGuard::amountsMatch('0.10000000', '0.1'));
		$this->assertTrue(CheckoutGuard::amountsMatch('-5.50', '-5.5'));
		$this->assertFalse(CheckoutGuard::amountsMatch('10.00', '10.01'));
	}

	public function testNonNegativeAmountValidation(): void
	{
		$this->assertTrue(CheckoutGuard::isNonNegativeAmount('0'));
		$this->assertTrue(CheckoutGuard::isNonNegativeAmount('0.00'));
		$this->assertTrue(CheckoutGuard::isNonNegativeAmount('12.99'));
		$this->assertFalse(CheckoutGuard::isNonNegativeAmount('-0.01'));
		$this->assertFalse(CheckoutGuard::isNonNegativeAmount(''));
		$this->assertFalse(CheckoutGuard::isNonNegativeAmount('1e3'));
		$this->assertFalse(CheckoutGuard::isNonNegativeAmount('abc'));
		$this->assertFalse(CheckoutGuard::isNonNegativeAmount("1\n"), 'A trailing newline must not pass: BCMath rejects it with a ValueError');
		$this->assertFalse(CheckoutGuard::isNonNegativeAmount("1.2\n3"));
		$this->assertFalse(CheckoutGuard::isNonNegativeAmount("\n1"));
	}

	public function testPositiveAmountValidation(): void
	{
		$this->assertFalse(CheckoutGuard::isPositiveAmount('0'));
		$this->assertFalse(CheckoutGuard::isPositiveAmount('0.00'));
		$this->assertFalse(CheckoutGuard::isPositiveAmount('-1'));
		$this->assertTrue(CheckoutGuard::isPositiveAmount('0.00000001'));
		$this->assertTrue(CheckoutGuard::isPositiveAmount('12.99'));
		$this->assertTrue(CheckoutGuard::isPositiveAmount('0.000000005'), 'Positivity must not be truncated to a fixed comparison scale');
		$this->assertTrue(CheckoutGuard::isPositiveAmount('0.000000000000000001'));
	}

	public function testConvertAmountValidatesAndScalesInputs(): void
	{
		$this->assertSame('12.3456789', CheckoutGuard::convertAmount('0.001', '12345.6789'));
		$this->assertSame('0.5', CheckoutGuard::convertAmount('0.5', '1'));
		$this->assertNull(CheckoutGuard::convertAmount('0', '12345'));
		$this->assertNull(CheckoutGuard::convertAmount('-0.001', '12345'));
		$this->assertNull(CheckoutGuard::convertAmount('0.001', '-2'));
		$this->assertNull(CheckoutGuard::convertAmount('0.001', 'not-a-rate'));
		$this->assertNull(CheckoutGuard::convertAmount('abc', '2'));
		$this->assertNull(CheckoutGuard::convertAmount("1\n", '2'), 'A trailing newline in the value must be rejected before BCMath sees it');
		$this->assertNull(CheckoutGuard::convertAmount('1', "2\n"), 'A trailing newline in the rate must be rejected before BCMath sees it');
	}

	/** @dataProvider highPrecisionInputs */
	public function testConvertAmountPreservesHighPrecisionInputs(string $value, string $rate, string $expected): void
	{
		$this->assertSame($expected, CheckoutGuard::convertAmount($value, $rate));
	}

	public function highPrecisionInputs(): array
	{
		return [
			'8 decimals'                  => ['0.00000001', '1', '0.00000001'],
			'16 decimals'                 => ['0.0000000000000001', '1', '0.0000000000000001'],
			'18 decimals'                 => ['0.000000000000000123', '1', '0.000000000000000123'],
			'tiny positive with rate'     => ['0.000000005', '1', '0.000000005'],
			'rate adds fractional digits' => ['4.9999999999999999', '0.001', '0.0049999999999999999'],
		];
	}

	public function testCanCreateOrderRequiresAtLeastOnePaymentState(): void
	{
		$this->assertFalse(CheckoutGuard::canCreateOrder(false, false, false, false, false));
		$this->assertTrue(CheckoutGuard::canCreateOrder(true, false, false, false, false));
		$this->assertTrue(CheckoutGuard::canCreateOrder(false, true, false, false, false));
		$this->assertTrue(CheckoutGuard::canCreateOrder(false, false, true, false, false));
		$this->assertTrue(CheckoutGuard::canCreateOrder(false, false, false, true, false));
		$this->assertTrue(CheckoutGuard::canCreateOrder(false, false, false, false, true));
	}

	public function testPaidTransitionCannotDowngradeProtectedPaidOrder(): void
	{
		$this->assertTrue(CheckoutGuard::paidTransitionAllowed(false, true, false));
		$this->assertTrue(CheckoutGuard::paidTransitionAllowed(true, false, false));
		$this->assertTrue(CheckoutGuard::paidTransitionAllowed(true, true, true));
		$this->assertFalse(CheckoutGuard::paidTransitionAllowed(true, true, false));
	}
}
