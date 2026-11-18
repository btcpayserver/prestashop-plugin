<?php

namespace BTCPay\Tests\Invoice;

use BTCPay\Entity\BitcoinPayment;
use BTCPay\Factory\CustomerMessage;
use BTCPay\Invoice\Processor;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class LatePaymentNotificationTest extends TestCase
{
	public function testUsesMerchantErrorLogAndPrivateNoteWithoutSeparateMail(): void
	{
		include __DIR__ . '/LatePaymentNotificationFixtures.php';

		$reflection = new \ReflectionClass(Processor::class);
		$processor  = $reflection->newInstanceWithoutConstructor();
		$context    = $reflection->getProperty('context');
		$context->setAccessible(true);
		$context->setValue($processor, new LatePaymentContext());
		$method = $reflection->getMethod('notifyLatePayment');
		$method->setAccessible(true);
		$method->invoke($processor, new BitcoinPayment(), new \Order(), '14.52', 'tx-42');

		$this->assertCount(1, \PrestaShopLogger::$entries);
		$this->assertSame(\PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, \PrestaShopLogger::$entries[0]['severity']);
		$this->assertCount(1, CustomerMessage::$notes);
		$details = CustomerMessage::$notes[0];
		$this->assertSame(\PrestaShopLogger::$entries[0]['message'], $details);
		$this->assertStringContainsString('order #42, invoice invoice-42, payment tx-42, amount 14.52 EUR.', $details);
		$this->assertStringContainsString("Pagamento arrivato dopo l'annullamento", $details);
		$this->assertSame([], \Mail::$calls);
	}
}
