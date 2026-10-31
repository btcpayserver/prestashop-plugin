<?php

namespace BTCPay\Tests\Controller;

use BTCPay\Constants;
use BTCPay\Controller\Admin\Improve\Payment\ConfigureController;
use BTCPay\Form\Data\Server;
use BTCPay\Server\Client;
use BTCPayServer\Http\ClientInterface;
use PHPUnit\Framework\TestCase;
use PrestaShop\PrestaShop\Adapter\Configuration;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/ConfigureControllerFlowFixtures.php';

/**
 * Controller flow tests: run editServerSettingsAction against the real
 * Greenfield client over a faked HTTP layer and verify rollback behaviour.
 */
class ConfigureControllerFlowTest extends TestCase
{
	private const OLD_HOST    = 'https://old-btcpay.example';
	private const NEW_HOST    = 'https://new-btcpay.example';
	private const OLD_STORE   = 'store-old';
	private const NEW_STORE   = 'store-new';
	private const OLD_WEBHOOK = 'webhook-old';
	private const NEW_WEBHOOK = 'webhook-new';

	private const OLD_CONFIGURATION = [
		Constants::CONFIGURATION_BTCPAY_HOST           => self::OLD_HOST,
		Constants::CONFIGURATION_BTCPAY_API_KEY        => 'old-key',
		Constants::CONFIGURATION_BTCPAY_STORE_ID       => self::OLD_STORE,
		Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID     => self::OLD_WEBHOOK,
		Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET => 'old-secret',
	];

	private FlowGreenfieldHttp $http;

	private ConfigureController $controller;

	public function testFailedUpdateKeepsPreviousWebhookAndRestoresPreviousConfiguration(): void
	{
		// The old webhook exists on the old server
		$this->http->on('GET', 'api-keys/current', 200, $this->apiKeyResponse(self::NEW_STORE));
		$this->http->on('GET', 'server/info', 200, $this->serverInfoResponse());
		$this->http->on('GET', 'stores/' . self::OLD_STORE . '/webhooks/' . self::OLD_WEBHOOK, 200, $this->webhookResponse(self::OLD_WEBHOOK));
		// The new store has no payment methods configured: the update fails with a temporary 503
		$this->http->on('GET', 'stores/' . self::NEW_STORE . '/payment-methods', 503, 'gateway error');
		$this->http->on('GET', 'stores/' . self::NEW_STORE, 200, $this->storeResponse(self::NEW_STORE));

		$response = $this->runSettingsUpdate(new Server(self::NEW_HOST, 'new-key'));

		// The update failed after the save: nothing may point at, or delete, the previous webhook
		$this->assertSame([], \array_column($this->requestsAgainstOldHost(), 'method'), 'The previous webhook must not be touched before the replacement exists');

		// The complete previous configuration is restored
		$this->assertSame(self::OLD_CONFIGURATION, Configuration::$values);

		// The merchant is told the server is temporarily unavailable
		$this->assertFlashesContain('error', 'temporarily unavailable');

		$this->assertInstanceOf(RedirectResponse::class, $response);
	}

	public function testSuccessfulUpdateReplacesWebhookBeforeRemovingPreviousOne(): void
	{
		$this->http->on('GET', 'api-keys/current', 200, $this->apiKeyResponse(self::NEW_STORE));
		$this->http->on('GET', 'server/info', 200, $this->serverInfoResponse());
		$this->http->on('GET', 'stores/' . self::NEW_STORE . '/webhooks', 404, '{}');
		$this->http->on('GET', 'stores/' . self::NEW_STORE . '/payment-methods', 200, $this->paymentMethodsResponse());
		$this->http->on('GET', 'stores/' . self::NEW_STORE, 200, $this->storeResponse(self::NEW_STORE));
		$this->http->on('POST', 'stores/' . self::NEW_STORE . '/webhooks', 200, fn (string $body) => $this->createdWebhookResponse(self::NEW_WEBHOOK, $body));
		$this->http->on('DELETE', 'stores/' . self::OLD_STORE . '/webhooks/' . self::OLD_WEBHOOK, 200, '{}');

		$this->runSettingsUpdate(new Server(self::NEW_HOST, 'new-key'));

		$methods = \array_column($this->http->requests, 'method');
		$this->assertContains('POST', $methods, 'A replacement webhook must have been created');
		$this->assertContains('DELETE', $methods, 'The previous webhook must have been removed');

		// The previous webhook is only removed after the replacement was created
		$postIndex  = (int) \array_search('POST', $methods, true);
		$deleteIndex = (int) \array_search('DELETE', $methods, true);
		$this->assertGreaterThan($postIndex, $deleteIndex);

		// The new configuration is stored completely
		$this->assertSame(self::NEW_HOST, Configuration::$values[Constants::CONFIGURATION_BTCPAY_HOST]);
		$this->assertSame('new-key', Configuration::$values[Constants::CONFIGURATION_BTCPAY_API_KEY]);
		$this->assertSame(self::NEW_STORE, Configuration::$values[Constants::CONFIGURATION_BTCPAY_STORE_ID]);
		$this->assertSame(self::NEW_WEBHOOK, Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID]);

		// The stored secret is the one the webhook was created with
		$createdBody = \json_decode($this->lastRequestByMethod('POST')['body'], true);
		$this->assertSame($createdBody['secret'], Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET]);
	}

	public function testSuccessfulUpdateKeepsPreviousWebhookWhenStillInUse(): void
	{
		// Only the API key changes: same host, same store, same webhook
		$this->http->on('GET', 'api-keys/current', 200, $this->apiKeyResponse(self::OLD_STORE));
		$this->http->on('GET', 'server/info', 200, $this->serverInfoResponse());
		$this->http->on('GET', 'stores/' . self::OLD_STORE . '/webhooks/' . self::OLD_WEBHOOK, 200, $this->webhookResponse(self::OLD_WEBHOOK));
		$this->http->on('GET', 'stores/' . self::OLD_STORE . '/payment-methods', 200, $this->paymentMethodsResponse());
		$this->http->on('GET', 'stores/' . self::OLD_STORE, 200, $this->storeResponse(self::OLD_STORE));

		$this->runSettingsUpdate(new Server(self::OLD_HOST, 'new-key'));

		$methods = \array_column($this->http->requests, 'method');
		$this->assertNotContains('POST', $methods, 'The webhook is still valid and must be reused');
		$this->assertNotContains('DELETE', $methods, 'The webhook still in use must not be removed');

		$this->assertSame('new-key', Configuration::$values[Constants::CONFIGURATION_BTCPAY_API_KEY]);
		$this->assertSame(self::OLD_WEBHOOK, Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID]);
		$this->assertSame('old-secret', Configuration::$values[Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET]);
	}

	public function testFormSaveErrorRestoresPreviousConfiguration(): void
	{
		$handler = $this->createHandler(new Server(self::NEW_HOST, 'new-key'));
		$handler->saveErrors = ['The host could not be validated.'];

		$this->runSettingsUpdate(null, $handler);

		// The save wrote the submitted host and API key before failing: they must be rolled back
		$this->assertSame(self::OLD_CONFIGURATION, Configuration::$values);

		$this->assertFlashesContain('error', 'The host could not be validated.');
		$this->assertFlashesContain('error', 'Your previous configuration was restored.');

		// No BTCPay API call happened
		$this->assertSame([], $this->http->requests);
	}

	public function testFormSaveExceptionRestoresPreviousConfiguration(): void
	{
		$handler = $this->createHandler(new Server(self::NEW_HOST, 'new-key'));
		$handler->saveException = new \RuntimeException('Storage backend failed');

		$this->runSettingsUpdate(null, $handler);

		$this->assertSame(self::OLD_CONFIGURATION, Configuration::$values);

		$this->assertFlashesContain('error', 'Storage backend failed');
		$this->assertFlashesContain('error', 'Your previous configuration was restored.');

		$this->assertSame([], $this->http->requests);
	}

	protected function setUp(): void
	{
		Configuration::$values = self::OLD_CONFIGURATION;
		$this->http            = new FlowGreenfieldHttp();
	}

	private function runSettingsUpdate(?Server $serverData, ?FlowFormHandler $serverHandler = null): RedirectResponse
	{
		$serverHandler ??= $this->createHandler($serverData ?? new Server(self::NEW_HOST, 'new-key'));

		$module       = new \BTCPay();
		$controller = new class($module, new FlowValidator(), $serverHandler, new FlowFormHandler(new FlowForm(new Server(self::NEW_HOST, null)))) extends ConfigureController {
			public ?ClientInterface $httpClient = null;

			protected function createServerClient(string $host, ?string $apiKey): Client
			{
				return new Client($host, (string) $apiKey, $this->httpClient);
			}
		};

		$controller->testConfiguration = new Configuration();
		$controller->httpClient        = $this->http;

		$this->controller = $controller;

		/** @var RedirectResponse $response */
		$response = $controller->editServerSettingsAction(new Request());

		return $response;
	}

	private function createHandler(Server $serverData): FlowFormHandler
	{
		return new FlowFormHandler(new FlowForm($serverData));
	}

	private function apiKeyResponse(string $storeId): string
	{
		return (string) \json_encode(['apiKey' => 'new-key', 'permissions' => ['btcpay.store:' . $storeId]]);
	}

	private function serverInfoResponse(): string
	{
		return (string) \json_encode(['version' => '1.13.0', 'fullySynched' => true]);
	}

	private function storeResponse(string $storeId): string
	{
		return (string) \json_encode(['id' => $storeId, 'name' => 'Test Store']);
	}

	private function paymentMethodsResponse(): string
	{
		return (string) \json_encode([['paymentMethodId' => 'BTC-CHAIN', 'cryptoCode' => 'BTC', 'enabled' => true]]);
	}

	private function webhookResponse(string $webhookId): string
	{
		return (string) \json_encode(['id' => $webhookId, 'url' => 'https://shop.example/module/btcpay/webhook']);
	}

	private function createdWebhookResponse(string $webhookId, string $requestBody): string
	{
		return (string) \json_encode(['id' => $webhookId, 'secret' => \json_decode($requestBody, true)['secret'], 'url' => 'https://shop.example/module/btcpay/webhook']);
	}

	private function requestsAgainstOldHost(): array
	{
		return \array_values(\array_filter($this->http->requests, static fn (array $request) => \str_contains($request['url'], self::OLD_HOST)));
	}

	private function lastRequestByMethod(string $method): array
	{
		$matching = \array_values(\array_filter($this->http->requests, static fn (array $request) => $method === $request['method']));

		return $matching[\count($matching) - 1];
	}

	private function assertFlashesContain(string $type, string $needle): void
	{
		$messages = \array_column(\array_filter($this->controller->flashes, static fn (array $flash) => $type === $flash['type']), 'message');

		$matching = \array_filter($messages, static fn (string $message) => \str_contains($message, $needle));

		$this->assertNotEmpty($matching, \sprintf('Expected an %s flash containing "%s", got: %s', $type, $needle, \json_encode($this->controller->flashes)));
	}
}
