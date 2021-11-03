<?php

namespace BTCPay\Server;

use BTCPay\LegacyOrderBitcoinRepository;
use BTCPayServer\Client\Client as BaseClient;
use BTCPayServer\PrivateKey;
use BTCPayServer\TokenInterface;

class Client extends BaseClient
{
	/**
	 * @var Encryption
	 */
	private $encryption;

	/**
	 * @var LegacyOrderBitcoinRepository
	 */
	private $repository;

	public function __construct()
	{
		$this->encryption = new Encryption();
		$this->repository = new LegacyOrderBitcoinRepository();

		// Set our own CURL adapter, always
		$this->setAdapter(new CurlAdapter());
	}

	public function getEncryption(): Encryption
	{
		return $this->encryption;
	}

	public function getBTCPayRedirect(\Cart $cart): ?string
	{
		// Check if we have a cart ID we can use
		if (empty($cart->id)) {
			return null;
		}

		if (null === ($orderBitcoin = $this->repository->getOneByCartID($cart->id))) {
			return null;
		}

		if (empty($redirect = $orderBitcoin->getRedirect())) {
			return null;
		}

		$errorReporting = error_reporting();
		error_reporting(\E_ALL & ~\E_NOTICE & ~\E_STRICT & ~\E_DEPRECATED & ~\E_WARNING);
		$invoice = $this->getInvoice($orderBitcoin->getInvoiceId());
		error_reporting($errorReporting);

		$status = $invoice->getStatus();
		if ('invalid' === $status || 'expired' === $status) {
			return null;
		}

		return $redirect;
	}

	public static function createFromConfiguration(): self
	{
		$client = new self();
		$client->setUri(self::getURI(\Configuration::get('BTCPAY_URL')));

		$privateKey = $client->getEncryption()->decrypt(\Configuration::get('BTCPAY_KEY'));
		if (!$privateKey instanceof PrivateKey) {
			throw new \RuntimeException('Could not decrypted the stored private key', 3);
		}

		$token = $client->getEncryption()->decrypt(\Configuration::get('BTCPAY_TOKEN'));
		if (!$token instanceof TokenInterface) {
			throw new \RuntimeException('Could not decrypted the stored token', 3);
		}

		$client->setPrivateKey($privateKey);
		$client->setPublicKey($privateKey->getPublicKey());

		// We need to do some extra work for the token
		$client->setToken(Token::createToken($token->getToken()));

		return $client;
	}

	/**
	 * Inject ports into the URL, because the client somehow demands a port.
	 */
	public static function getURI(string $url): string
	{
		// Sanitize URL first
		$url = rtrim($url, '/\\');
		if ('https' === (parse_url($url, \PHP_URL_SCHEME))) {
			return sprintf('%s:443', $url);
		}

		return sprintf('%s:80', $url);
	}
}
