<?php

namespace BTCPay\Tests\Server;

use BTCPay\Constants;
use BTCPay\Exception\BTCPayException;
use BTCPay\Server\Webhook;
use BTCPay\Tests\Stubs\WebhookHttpClient;
use BTCPayServer\Exception\ConnectException;
use BTCPayServer\Exception\RequestException;
use PHPUnit\Framework\TestCase;
use PrestaShop\PrestaShop\Adapter\Configuration;

require_once \dirname(__DIR__) . '/stubs/WebhookHttpClient.php';

class WebhookTest extends TestCase
{
	private const WEBHOOKS_URL = 'https://btcpay.example/api/v1/stores/store-a/webhooks';

	private WebhookHttpClient $httpClient;
	private Webhook $webhook;

	public function testPreservesHealthyWebhook(): void
	{
		$configuration = Configuration::$values;
		$webhooks = $this->httpClient->webhooks;

		$this->webhook->ensureWebhook('store-a');

		$this->assertSame($configuration, Configuration::$values);
		$this->assertSame($webhooks, $this->httpClient->webhooks);
		$this->assertSame(['GET'], \array_column($this->httpClient->requests, 'method'));
	}

	/** @dataProvider missingSecrets */
	public function testRecreatesWebhookWithoutSecret($secret): void
	{
		Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET] = $secret;

		$this->webhook->ensureWebhook('store-a');

		$this->assertArrayNotHasKey(self::WEBHOOKS_URL . '/webhook-old', $this->httpClient->webhooks);
		$this->assertCount(1, $this->httpClient->webhooks);
		$this->assertReplacementSaved();
	}

	public function missingSecrets(): array
	{
		return [[''], [null], [false]];
	}

	/** @dataProvider missingWebhookIds */
	public function testCreatesWebhookWithoutSavedId($id): void
	{
		Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID] = $id;
		$this->httpClient->webhooks = [];

		$this->webhook->ensureWebhook('store-a');

		$this->assertReplacementSaved();
		$this->assertSame(['POST'], \array_column($this->httpClient->requests, 'method'));
	}

	public function missingWebhookIds(): array
	{
		return [[null], ['']];
	}

	/** @dataProvider webhookSecrets */
	public function testRecreatesMissingRemoteWebhook(string $secret): void
	{
		Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET] = $secret;
		$this->httpClient->webhooks = [];

		$this->webhook->ensureWebhook('store-a');

		$this->assertReplacementSaved();
	}

	public function webhookSecrets(): array
	{
		return [[''], ['existing-secret']];
	}

	/** @dataProvider deletionFailures */
	public function testPreservesConfigurationAndStopsWhenDeletionFails(int $status): void
	{
		Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET] = '';
		$this->httpClient->deleteStatus = $status;
		$configuration = Configuration::$values;
		$webhooks = $this->httpClient->webhooks;

		$this->expectException(RequestException::class);
		$this->expectExceptionCode($status);

		try {
			$this->webhook->ensureWebhook('store-a');
		} finally {
			$this->assertSame($configuration, Configuration::$values);
			$this->assertSame($webhooks, $this->httpClient->webhooks);
			$this->assertNotContains('POST', \array_column($this->httpClient->requests, 'method'));
		}
	}

	public function deletionFailures(): array
	{
		return [[403], [500], [503]];
	}

	public function testPreservesConfigurationWhenDeletionCannotConnect(): void
	{
		Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET] = '';
		$this->httpClient->deleteException = new ConnectException('Connection timed out', 28);
		$configuration = Configuration::$values;

		$this->expectException(ConnectException::class);

		try {
			$this->webhook->ensureWebhook('store-a');
		} finally {
			$this->assertSame($configuration, Configuration::$values);
			$this->assertArrayHasKey(self::WEBHOOKS_URL . '/webhook-old', $this->httpClient->webhooks);
			$this->assertNotContains('POST', \array_column($this->httpClient->requests, 'method'));
		}
	}

	public function testCanRetryAfterDeletionFails(): void
	{
		Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET] = '';
		$this->httpClient->deleteStatus = 503;

		try {
			$this->webhook->ensureWebhook('store-a');
			$this->fail('A failed deletion must stop replacement.');
		} catch (RequestException $exception) {
			$this->assertSame(503, $exception->getCode());
		}

		$this->httpClient->deleteStatus = 200;
		$this->webhook->ensureWebhook('store-a');

		$this->assertArrayNotHasKey(self::WEBHOOKS_URL . '/webhook-old', $this->httpClient->webhooks);
		$this->assertReplacementSaved();
	}

	public function testDeletesFromRequestedStoreWithoutSavedStoreId(): void
	{
		unset(Configuration::$values[Constants::CONFIGURATION_BTCPAY_STORE_ID]);
		Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET] = '';

		$this->webhook->ensureWebhook('store-a');

		$this->assertArrayNotHasKey(self::WEBHOOKS_URL . '/webhook-old', $this->httpClient->webhooks);
		$this->assertReplacementSaved();
	}

	public function testCanRetryWhenCreationFailsAfterDeletion(): void
	{
		Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET] = '';
		$this->httpClient->createStatus = 503;
		$configuration = Configuration::$values;

		try {
			$this->webhook->ensureWebhook('store-a');
			$this->fail('A failed creation must not be saved.');
		} catch (RequestException $exception) {
			$this->assertSame(503, $exception->getCode());
		}

		$this->assertSame($configuration, Configuration::$values);
		$this->assertSame([], $this->httpClient->webhooks);

		$this->httpClient->createStatus = 200;
		$this->webhook->ensureWebhook('store-a');

		$this->assertReplacementSaved();
	}

	/** @dataProvider invalidCreatedWebhooks */
	public function testDoesNotSaveInvalidReplacement(array $overrides): void
	{
		Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET] = '';
		$this->httpClient->createdOverrides = $overrides;
		$configuration = Configuration::$values;

		$this->expectException(BTCPayException::class);

		try {
			$this->webhook->ensureWebhook('store-a');
		} finally {
			$this->assertSame($configuration, Configuration::$values);
		}
	}

	public function invalidCreatedWebhooks(): array
	{
		return [[['id' => '']], [['secret' => '']], [['secret' => 'wrong-secret']]];
	}

	public function testReusesReplacementOnNextCall(): void
	{
		Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET] = '';
		$this->webhook->ensureWebhook('store-a');
		$configuration = Configuration::$values;
		$webhooks = $this->httpClient->webhooks;
		$this->httpClient->requests = [];

		$this->webhook->ensureWebhook('store-a');

		$this->assertSame($configuration, Configuration::$values);
		$this->assertSame($webhooks, $this->httpClient->webhooks);
		$this->assertSame(['GET'], \array_column($this->httpClient->requests, 'method'));
	}

	protected function setUp(): void
	{
		Configuration::$values = [
			Constants::CONFIGURATION_BTCPAY_STORE_ID       => 'store-a',
			Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID     => 'webhook-old',
			Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET => 'existing-secret',
		];
		\PrestaShopLogger::reset();
		$this->httpClient = new WebhookHttpClient();
		$this->httpClient->webhooks[self::WEBHOOKS_URL . '/webhook-old'] = ['id' => 'webhook-old'];
		$this->webhook = new Webhook('https://btcpay.example', 'test-api-key', $this->httpClient);
	}

	private function assertReplacementSaved(): void
	{
		$this->assertSame('webhook-new', Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID]);
		$secret = Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET];
		$this->assertMatchesRegularExpression('/^[a-f0-9]{48}$/', $secret);
		$remote = $this->httpClient->webhooks[self::WEBHOOKS_URL . '/webhook-new'];
		$this->assertSame($secret, $remote['secret']);
		$this->assertSame('https://shop.example/module/btcpay/webhook', $remote['url']);
	}
}
