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
	}

	public function testPositiveAmountValidation(): void
	{
		$this->assertFalse(CheckoutGuard::isPositiveAmount('0'));
		$this->assertFalse(CheckoutGuard::isPositiveAmount('0.00'));
		$this->assertFalse(CheckoutGuard::isPositiveAmount('-1'));
		$this->assertTrue(CheckoutGuard::isPositiveAmount('0.00000001'));
		$this->assertTrue(CheckoutGuard::isPositiveAmount('12.99'));
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
