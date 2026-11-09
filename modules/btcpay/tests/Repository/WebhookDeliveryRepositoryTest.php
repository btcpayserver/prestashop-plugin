<?php

namespace BTCPay\Tests\Repository;

use BTCPay\Repository\WebhookDeliveryRepository;
use PHPUnit\Framework\TestCase;

class WebhookDeliveryRepositoryTest extends TestCase
{
	/** @before */
	public function resetTestDoubles(): void
	{
		\Db::reset();
		\PrestaShopLogger::reset();
	}

	public function testFirstClaimSucceeds(): void
	{
		$this->assertTrue(WebhookDeliveryRepository::claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
		$this->assertCount(1, \Db::getInstance()->queries);
	}

	public function testDuplicateClaimIsRejected(): void
	{
		$this->assertTrue(WebhookDeliveryRepository::claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
		$this->assertFalse(WebhookDeliveryRepository::claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
	}

	public function testDifferentDeliveryIdsClaimIndependently(): void
	{
		$this->assertTrue(WebhookDeliveryRepository::claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
		$this->assertTrue(WebhookDeliveryRepository::claim('delivery-2', 'invoice-1', 'InvoiceSettled'));
		$this->assertFalse(WebhookDeliveryRepository::claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
	}

	public function testReleaseAllowsReprocessing(): void
	{
		$this->assertTrue(WebhookDeliveryRepository::claim('delivery-1', 'invoice-1', 'InvoiceSettled'));

		WebhookDeliveryRepository::release('delivery-1');

		$this->assertTrue(WebhookDeliveryRepository::claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
	}

	public function testEmptyDeliveryIdBypassesTracking(): void
	{
		$this->assertTrue(WebhookDeliveryRepository::claim('', 'invoice-1', 'InvoiceSettled'));
		$this->assertTrue(WebhookDeliveryRepository::claim('', 'invoice-1', 'InvoiceSettled'));
		$this->assertSame([], \Db::getInstance()->queries);
	}

	public function testClaimFailsOpenWhenDatabaseIsUnavailable(): void
	{
		\Db::getInstance()->unavailable = true;

		$this->assertTrue(WebhookDeliveryRepository::claim('delivery-1', 'invoice-1', 'InvoiceSettled'));
		$this->assertCount(1, \PrestaShopLogger::$entries);
		$this->assertSame(\PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, \PrestaShopLogger::$entries[0]['severity']);
	}

	public function testReleaseIsSilentWhenDatabaseIsUnavailable(): void
	{
		\Db::getInstance()->unavailable = true;

		WebhookDeliveryRepository::release('delivery-1');

		$this->assertCount(1, \PrestaShopLogger::$entries);
		$this->assertSame(\PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, \PrestaShopLogger::$entries[0]['severity']);
	}
}
