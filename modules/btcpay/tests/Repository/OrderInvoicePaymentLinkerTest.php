<?php

namespace BTCPay\Tests\Repository;

use BTCPay\Repository\OrderInvoicePaymentLinker;
use PHPUnit\Framework\TestCase;

class OrderInvoicePaymentLinkerTest extends TestCase
{
	/** @before */
	public function resetTestDoubles(): void
	{
		\Db::reset();
		\Cache::reset();
	}

	public function testLinksUnlinkedOrderPaymentsToInvoice(): void
	{
		$db = \Db::getInstance();
		$db->executeSResult = [
			['id_order_payment' => 11],
			['id_order_payment' => 12],
		];

		OrderInvoicePaymentLinker::linkUnlinkedPayments(7, 3);

		$this->assertCount(2, $db->inserts);
		$this->assertSame('order_invoice_payment', $db->inserts[0]['table']);
		$this->assertSame([
			'id_order_invoice' => 3,
			'id_order_payment' => 11,
			'id_order'         => 7,
		], $db->inserts[0]['data']);
		$this->assertSame([
			'id_order_invoice' => 3,
			'id_order_payment' => 12,
			'id_order'         => 7,
		], $db->inserts[1]['data']);
		$this->assertSame(['order_invoice_paid_*'], \Cache::$cleaned);
		$this->assertStringContainsString('o.id_order = 7', $db->queries[0]);
	}

	public function testDoesNothingWhenNothingUnlinked(): void
	{
		$db = \Db::getInstance();
		$db->executeSResult = [];

		OrderInvoicePaymentLinker::linkUnlinkedPayments(1, 2);

		$this->assertSame([], $db->inserts);
		$this->assertSame([], \Cache::$cleaned);
	}

	public function testThrowsWhenInsertFails(): void
	{
		$db = \Db::getInstance();
		$db->executeSResult = [['id_order_payment' => 5]];
		$db->insertFails    = true;

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Could not link order payment 5 to invoice 2');

		OrderInvoicePaymentLinker::linkUnlinkedPayments(1, 2);
	}
}
