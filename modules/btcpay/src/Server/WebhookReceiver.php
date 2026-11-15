<?php

declare(strict_types=1);

namespace BTCPay\Server;

use BTCPay;
use BTCPay\Constants;
use BTCPay\Invoice\Processor;
use BTCPay\Repository\BitcoinPaymentRepository;
use BTCPay\Repository\OrderInvoicePaymentLinker;
use BTCPay\Repository\OrderPaymentRepository;
use BTCPay\Repository\WebhookDeliveryRepository;
use Context;
use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;
use PrestaShopLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

if (!\defined('_PS_VERSION_')) {
	exit;
}

final class WebhookReceiver
{
	public function __construct(
		private readonly BTCPay $module,
		private readonly ShopConfigurationInterface $configuration,
		private readonly BitcoinPaymentRepository $payments,
		private readonly WebhookDeliveryRepository $deliveries,
		private readonly Connection $connection,
		private readonly OrderPaymentRepository $orderPayments,
		private readonly OrderInvoicePaymentLinker $invoicePayments,
	) {
	}

	/**
	 * Status codes:
	 * - 405 not a POST request;
	 * - 400 module inactive/not configured, or signature header missing;
	 * - 401 wrong signature;
	 * - 503 signature valid but BTCPay Server not reachable/valid: BTCPay redelivers later;
	 * - 500 processing error (logged);
	 * - 200 processed, ignored or test delivery.
	 *
	 * @return array{code: int, text: string}
	 */
	public function handle(Request $request): array
	{
		if (!$request->isMethod(Request::METHOD_POST)) {
			return ['code' => Response::HTTP_METHOD_NOT_ALLOWED, 'text' => 'Method Not Allowed'];
		}

		// BTCPay sends JSON; reject other media types before parsing the body
		if (false === WebhookRequestGuard::hasJsonContentType($request->headers->get('Content-Type'))) {
			return ['code' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE, 'text' => 'Unsupported Media Type'];
		}

		// Reject oversized requests before reading the body when the size is declared
		$declaredLength = $request->headers->get('Content-Length');
		if (null !== $declaredLength && \ctype_digit((string) $declaredLength) && false === WebhookRequestGuard::isWithinBodyLimit((int) $declaredLength)) {
			return ['code' => Response::HTTP_REQUEST_ENTITY_TOO_LARGE, 'text' => 'Payload Too Large'];
		}

		$signature = (string) $request->headers->get(Constants::BTCPAY_HEADER_SIG, '');
		$secret = (string) $this->configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET);

		if (!$this->module->active || '' === $signature || '' === $secret) {
			return ['code' => Response::HTTP_BAD_REQUEST, 'text' => 'Bad Request'];
		}

		$body = $request->getContent();

		// Enforce the body limit when no usable Content-Length header was supplied
		if (false === WebhookRequestGuard::isWithinBodyLimit(\strlen($body))) {
			return ['code' => Response::HTTP_REQUEST_ENTITY_TOO_LARGE, 'text' => 'Payload Too Large'];
		}

		if (!\BTCPayServer\Client\Webhook::isIncomingWebhookRequestValid($body, \trim($signature), $secret)) {
			PrestaShopLogger::addLog('Invalid BTCPay Server payment notification message received - signature did not match.', PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING);

			return ['code' => Response::HTTP_UNAUTHORIZED, 'text' => 'Unauthorized'];
		}

		// Reject malformed JSON before handing the request to the handler
		if (false === WebhookRequestGuard::isValidJson($body)) {
			PrestaShopLogger::addLog('Invalid BTCPay Server payment notification message received - body is not a JSON object.', PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING);

			return ['code' => Response::HTTP_BAD_REQUEST, 'text' => 'Bad Request'];
		}

		$data = \json_decode($body, true);
		if (\is_array($data) && isset($data['invoiceId']) && \is_string($data['invoiceId']) && \str_contains($data['invoiceId'], '__test__')) {
			PrestaShopLogger::addLog('[INFO] Received BTCPay Server test webhook');

			return ['code' => Response::HTTP_OK, 'text' => 'OK'];
		}

		// A correctly signed delivery means BTCPay is up again: close the unreachable pause and try once
		CurlAdapter::resume();
		$client = Client::createFromConfiguration($this->configuration);
		if (null === $client || false === $client->isValid()) {
			PrestaShopLogger::addLog('[ERROR] A signed BTCPay webhook was received but BTCPay Server could not be queried. BTCPay Server will redeliver it.', PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR);

			return ['code' => Response::HTTP_SERVICE_UNAVAILABLE, 'text' => 'Service Unavailable'];
		}

		try {
			$context = Context::getContext();
			$processor = new Processor($this->module, $context, $this->configuration, $client, $this->payments, $this->connection, $this->orderPayments, $this->invoicePayments);
			(new WebhookHandler($context, $this->configuration, $this->payments, $this->deliveries, $processor))->process($request);
		} catch (Throwable $throwable) {
			PrestaShopLogger::addLog(\sprintf('[ERROR] Webhook processing failed: %s', $throwable->getMessage()), PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, $throwable->getCode());

			return ['code' => Response::HTTP_INTERNAL_SERVER_ERROR, 'text' => 'Internal Server Error'];
		}

		return ['code' => Response::HTTP_OK, 'text' => 'OK'];
	}
}
