<?php

declare(strict_types=1);

namespace BTCPay\Tests\Repository;

use BTCPay\Repository\WebhookDeliveryRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use PrestaShopLogger;
use RuntimeException;

class WebhookDeliveryRepositoryTest extends TestCase
{
	private Connection $connection;

	/** @var array<string, true> */
	private array $claimed = [];

	private int $statements = 0;

	private bool $unavailable = false;

	private WebhookDeliveryRepository $repository;

	protected function setUp(): void
	{
		PrestaShopLogger::reset();
		$this->claimed = [];
		$this->statements = 0;
		$this->unavailable = false;
		$this->connection = $this->createStub(Connection::class);
		$this->connection->method('executeStatement')->willReturnCallback(function (string $sql, array $params = [], array $types = []): int {
			if ($this->unavailable) {
				throw new RuntimeException('database unavailable');
			}

			++$this->statements;
			$deliveryId = (string) ($params['deliveryId'] ?? '');
			if (\str_contains($sql, 'INSERT')) {
				if (isset($this->claimed[$deliveryId])) {
					return 0;
				}

				$this->claimed[$deliveryId] = true;

				return 1;
			}

			unset($this->claimed[$deliveryId]);

			return 1;
		});
		$this->repository = new WebhookDeliveryRepository($this->connection);
	}

	public function testFirstClaimSucceeds(): void
	{
		$this->assertTrue($this->repository->claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
		$this->assertSame(1, $this->statements);
	}

	public function testDuplicateClaimIsRejected(): void
	{
		$this->assertTrue($this->repository->claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
		$this->assertFalse($this->repository->claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
	}

	public function testDifferentDeliveryIdsClaimIndependently(): void
	{
		$this->assertTrue($this->repository->claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
		$this->assertTrue($this->repository->claim('delivery-2', 'invoice-1', 'InvoiceSettled'));
		$this->assertFalse($this->repository->claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
	}

	public function testReleaseAllowsReprocessing(): void
	{
		$this->assertTrue($this->repository->claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
		$this->repository->release('delivery-1');
		$this->assertTrue($this->repository->claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
	}

	public function testEmptyDeliveryIdBypassesTracking(): void
	{
		$this->assertTrue($this->repository->claim('', 'invoice-1', 'InvoiceSettled'));
		$this->assertTrue($this->repository->claim('', 'invoice-1', 'InvoiceSettled'));
		$this->assertSame(0, $this->statements);
	}

	public function testClaimFailsOpenWhenDatabaseIsUnavailable(): void
	{
		$this->unavailable = true;

		$this->assertTrue($this->repository->claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
		$this->assertCount(1, PrestaShopLogger::$entries);
		$this->assertSame(PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, PrestaShopLogger::$entries[0]['severity']);
	}

	public function testReleaseIsSilentWhenDatabaseIsUnavailable(): void
	{
		$this->unavailable = true;

		$this->repository->release('delivery-1');

		$this->assertCount(1, PrestaShopLogger::$entries);
		$this->assertSame(PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, PrestaShopLogger::$entries[0]['severity']);
	}
}
