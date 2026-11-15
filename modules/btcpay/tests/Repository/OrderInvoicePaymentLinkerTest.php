<?php

declare(strict_types=1);

namespace BTCPay\Tests\Repository;

use BTCPay\Repository\OrderInvoicePaymentLinker;
use Cache;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class OrderInvoicePaymentLinkerTest extends TestCase
{
	private Connection $connection;

	/** @var list<string> */
	private array $queries = [];

	/** @var list<array{table: string, data: array<string, int>}> */
	private array $inserts = [];

	/** @var list<array<string, int>> */
	private array $rows = [];

	private bool $insertFails = false;

	protected function setUp(): void
	{
		Cache::reset();
		$this->queries = [];
		$this->inserts = [];
		$this->rows = [];
		$this->insertFails = false;
		$this->connection = $this->createStub(Connection::class);
		$this->connection->method('fetchAllAssociative')->willReturnCallback(function (string $sql, array $params = [], array $types = []): array {
			$this->queries[] = $sql;

			return $this->rows;
		});
		$this->connection->method('insert')->willReturnCallback(function (string $table, array $data, array $types = []): int {
			if ($this->insertFails) {
				throw new RuntimeException('insert failed');
			}

			$this->inserts[] = ['table' => $table, 'data' => $data];

			return 1;
		});
	}

	public function testLinksUnlinkedOrderPaymentsToInvoice(): void
	{
		$this->rows = [
			['id_order_payment' => 11],
			['id_order_payment' => 12],
		];

		(new OrderInvoicePaymentLinker($this->connection))->linkUnlinkedPayments(7, 3);

		$this->assertCount(2, $this->inserts);
		$this->assertSame('ps_order_invoice_payment', $this->inserts[0]['table']);
		$this->assertSame([
			'id_order_invoice' => 3,
			'id_order_payment' => 11,
			'id_order' => 7,
		], $this->inserts[0]['data']);
		$this->assertSame([
			'id_order_invoice' => 3,
			'id_order_payment' => 12,
			'id_order' => 7,
		], $this->inserts[1]['data']);
		$this->assertSame(['order_invoice_paid_*'], Cache::$cleaned);
		$this->assertStringContainsString('o.id_order = :orderId', $this->queries[0]);
	}

	public function testDoesNothingWhenNothingUnlinked(): void
	{
		$this->rows = [];

		(new OrderInvoicePaymentLinker($this->connection))->linkUnlinkedPayments(1, 2);

		$this->assertSame([], $this->inserts);
		$this->assertSame([], Cache::$cleaned);
	}

	public function testThrowsWhenInsertFails(): void
	{
		$this->rows = [['id_order_payment' => 5]];
		$this->insertFails = true;

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Could not link order payment 5 to invoice 2');

		(new OrderInvoicePaymentLinker($this->connection))->linkUnlinkedPayments(1, 2);
	}
}
