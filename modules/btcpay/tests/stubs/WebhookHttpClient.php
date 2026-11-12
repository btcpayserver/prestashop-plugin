<?php

namespace BTCPay\Tests\Stubs;

use BTCPayServer\Http\ClientInterface;
use BTCPayServer\Http\Response;
use BTCPayServer\Http\ResponseInterface;

/**
 * Models remote webhook state while exercising the real Greenfield client.
 */
class WebhookHttpClient implements ClientInterface
{
	public array $webhooks = [];
	public array $requests = [];
	public int $deleteStatus = 200;
	public int $createStatus = 200;
	public ?\Throwable $deleteException = null;
	public array $createdOverrides = [];

	public function request(string $method, string $url, array $headers = [], string $body = ''): ResponseInterface
	{
		$this->requests[] = ['method' => $method, 'url' => $url, 'body' => $body];

		if ('GET' === $method) {
			return new Response(isset($this->webhooks[$url]) ? 200 : 404, \json_encode($this->webhooks[$url] ?? []), []);
		}

		if ('DELETE' === $method) {
			if (null !== $this->deleteException) {
				throw $this->deleteException;
			}

			$status = isset($this->webhooks[$url]) ? $this->deleteStatus : 404;
			if (200 === $status) {
				unset($this->webhooks[$url]);
			}

			return new Response($status, '{}', []);
		}

		if ('POST' === $method) {
			$data = \array_merge(\json_decode($body, true), ['id' => 'webhook-new'], $this->createdOverrides);
			if (200 === $this->createStatus) {
				$this->webhooks[$url . '/webhook-new'] = $data;
			}

			return new Response($this->createStatus, \json_encode($data), []);
		}

		throw new \LogicException('Unexpected HTTP method: ' . $method);
	}
}
