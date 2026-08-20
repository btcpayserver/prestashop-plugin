<?php

namespace BTCPay\Server;

use BTCPayServer\Http\ClientInterface;
use BTCPayServer\Result\StoreRateSettings;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class StoreRate extends \BTCPayServer\Client\StoreRate
{
	public const SOURCE_PRIMARY  = 'primary';
	public const SOURCE_FALLBACK = 'fallback';

	public function __construct(string $baseUrl, string $apiKey, ?ClientInterface $client = null)
	{
		parent::__construct($baseUrl, $apiKey, $client);
	}

	/**
	 * @throws \JsonException
	 */
	public function getSettingsBySource(string $storeId, string $rateSource): StoreRateSettings
	{
		$url      = $this->getApiUrl() . 'stores/' . \urlencode($storeId) . '/rates/configuration/' . \urlencode($rateSource);
		$headers  = $this->getRequestHeaders();
		$method   = 'GET';
		$response = $this->getHttpClient()->request($method, $url, $headers);

		if (200 === $response->getStatus()) {
			return new StoreRateSettings(\json_decode($response->getBody(), true, 512, \JSON_THROW_ON_ERROR));
		}

		throw $this->getExceptionByStatusCode($method, $url, $response);
	}
}
