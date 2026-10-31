<?php

namespace BTCPay\Tests\Server;

use BTCPay\Constants;
use BTCPay\Server\ConfigurationSnapshot;
use PHPUnit\Framework\TestCase;
use PrestaShop\PrestaShop\Adapter\Configuration;

class ConfigurationSnapshotTest extends TestCase
{
	public function testRestoresCompletePreviousConfigurationAfterFailedUpdate(): void
	{
		Configuration::$values = [
			Constants::CONFIGURATION_BTCPAY_HOST           => 'https://old.example.com',
			Constants::CONFIGURATION_BTCPAY_API_KEY        => 'old-api-key',
			Constants::CONFIGURATION_BTCPAY_STORE_ID       => 'old-store-id',
			Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID     => 'old-webhook-id',
			Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET => 'old-webhook-secret',
		];

		$configuration = new Configuration();
		$snapshot      = ConfigurationSnapshot::capture($configuration);

		// Simulate a failed settings update that applied every submitted value before failing
		$configuration->set(Constants::CONFIGURATION_BTCPAY_HOST, 'https://new.example.com');
		$configuration->set(Constants::CONFIGURATION_BTCPAY_API_KEY, 'new-api-key');
		$configuration->set(Constants::CONFIGURATION_BTCPAY_STORE_ID, 'new-store-id');
		$configuration->set(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID, 'new-webhook-id');
		$configuration->set(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET, 'new-webhook-secret');

		$snapshot->restore($configuration);

		$this->assertSame('https://old.example.com', $configuration->get(Constants::CONFIGURATION_BTCPAY_HOST));
		$this->assertSame('old-api-key', $configuration->get(Constants::CONFIGURATION_BTCPAY_API_KEY));
		$this->assertSame('old-store-id', $configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID));
		$this->assertSame('old-webhook-id', $configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID));
		$this->assertSame('old-webhook-secret', $configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET));
	}

	public function testRestoreReinstatesValuesThatWereUnsetAtCaptureTime(): void
	{
		$configuration = new Configuration();
		$configuration->set(Constants::CONFIGURATION_BTCPAY_HOST, 'https://old.example.com');

		$snapshot = ConfigurationSnapshot::capture($configuration);

		// The failed update stored values for keys that were never configured before
		$configuration->set(Constants::CONFIGURATION_BTCPAY_HOST, 'https://new.example.com');
		$configuration->set(Constants::CONFIGURATION_BTCPAY_API_KEY, 'new-api-key');
		$configuration->set(Constants::CONFIGURATION_BTCPAY_STORE_ID, 'new-store-id');

		$snapshot->restore($configuration);

		$this->assertSame('https://old.example.com', $configuration->get(Constants::CONFIGURATION_BTCPAY_HOST));
		$this->assertNull($configuration->get(Constants::CONFIGURATION_BTCPAY_API_KEY));
		$this->assertNull($configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID));
		$this->assertNull($configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID));
		$this->assertNull($configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET));
	}

	public function testCaptureDoesNotModifyTheConfiguration(): void
	{
		Configuration::$values = [
			Constants::CONFIGURATION_BTCPAY_HOST    => 'https://old.example.com',
			Constants::CONFIGURATION_BTCPAY_API_KEY => 'old-api-key',
		];

		$configuration = new Configuration();

		ConfigurationSnapshot::capture($configuration);

		$this->assertSame([
			Constants::CONFIGURATION_BTCPAY_HOST    => 'https://old.example.com',
			Constants::CONFIGURATION_BTCPAY_API_KEY => 'old-api-key',
		], Configuration::$values);
	}

	public function testRestoreCanBeAppliedRepeatedlyAndIsIdempotent(): void
	{
		Configuration::$values = [
			Constants::CONFIGURATION_BTCPAY_HOST    => 'https://old.example.com',
			Constants::CONFIGURATION_BTCPAY_API_KEY => 'old-api-key',
		];

		$configuration = new Configuration();
		$snapshot      = ConfigurationSnapshot::capture($configuration);

		$configuration->set(Constants::CONFIGURATION_BTCPAY_HOST, 'https://new.example.com');

		$snapshot->restore($configuration);
		$snapshot->restore($configuration);

		$this->assertSame('https://old.example.com', $configuration->get(Constants::CONFIGURATION_BTCPAY_HOST));
		$this->assertSame('old-api-key', $configuration->get(Constants::CONFIGURATION_BTCPAY_API_KEY));
	}

	protected function setUp(): void
	{
		Configuration::$values = [];
	}
}
