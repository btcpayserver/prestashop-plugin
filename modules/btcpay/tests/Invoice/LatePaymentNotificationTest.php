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
	public function testNotifiesOnlyTheShopWithPrivateNoteAndWarning(): void
	{
		$this->notify();

		$this->assertCount(1, \PrestaShopLogger::$entries);
		$this->assertSame(\PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, \PrestaShopLogger::$entries[0]['severity']);
		$this->assertCount(1, CustomerMessage::$notes);
		$details = CustomerMessage::$notes[0];
		$this->assertSame(\PrestaShopLogger::$entries[0]['message'], $details);
		$this->assertStringContainsString('order #42, invoice invoice-42, payment tx-42, amount 14.52 EUR.', $details);
		$this->assertStringContainsString('Pagamento arrivato', $details);
		$this->assertCount(1, \Mail::$calls);
		$mail = \Mail::$calls[0];
		$this->assertSame(2, $mail[0]);
		$this->assertSame('late_payment', $mail[1]);
		$this->assertSame('owner@example.test', $mail[4]);
		$this->assertSame(3, $mail[12]);
		$this->assertSame($details, $mail[3]['{late_payment_details}']);
		$this->assertSame(\htmlspecialchars($details, \ENT_QUOTES, 'UTF-8'), $mail[3]['{late_payment_details_html}']);
		$this->assertStringContainsString("l'annullamento", $mail[3]['{late_payment_details}']);
		foreach (['en', 'it'] as $language) {
			foreach (['html', 'txt'] as $extension) {
				$this->assertStringContainsString('html' === $extension ? '{late_payment_details_html}' : '{late_payment_details}', \file_get_contents($mail[10] . $language . '/late_payment.' . $extension));
			}
		}
	}

	public function testMailFailureLeavesWarningAndNoteAndReportsFailure(): void
	{
		\Mail::$success = false;
		$this->notify();

		$this->assertCount(1, CustomerMessage::$notes);
		$this->assertCount(1, \Mail::$calls);
		$this->assertCount(2, \PrestaShopLogger::$entries);
		$this->assertSame(\PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, \PrestaShopLogger::$entries[1]['severity']);
	}

	protected function setUp(): void
	{
		include __DIR__ . '/LatePaymentNotificationFixtures.php';
	}

	private function notify(): void
	{
		$reflection = new \ReflectionClass(Processor::class);
		$processor  = $reflection->newInstanceWithoutConstructor();
		$context    = $reflection->getProperty('context');
		$context->setAccessible(true);
		$context->setValue($processor, new LatePaymentContext());
		$method = $reflection->getMethod('notifyLatePayment');
		$method->setAccessible(true);
		$method->invoke($processor, new BitcoinPayment(), new \Order(), '14.52', 'tx-42');
	}
}
