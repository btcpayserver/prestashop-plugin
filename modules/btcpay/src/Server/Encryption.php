<?php

namespace BTCPay\Server;

use BTCPayServer\Crypto\OpenSSLExtension;
use BTCPayServer\Key;
use BTCPayServer\PrivateKey;
use BTCPayServer\PublicKey;
use BTCPayServer\SinKey;
use BTCPayServer\Token;
use BTCPayServer\TokenInterface;

class Encryption
{
	/**
	 * @param object|Key|Token $data
	 *
	 * @return array|string
	 */
	public function encrypt(object $data)
	{
		if (empty($data)) {
			return [
				'key'        => 'The BTCPay payment plugin was called to encrypt data but no data was passed!',
				'domain'     => 'Admin.Catalog.Notification',
				'parameters' => [],
			];
		}

		if (40 !== \strlen($fingerprint = sha1(sha1(__DIR__)))) {
			return [
				'key'        => 'Invalid server fingerprint generated!',
				'domain'     => 'Admin.Catalog.Notification',
				'parameters' => [],
			];
		}

		$openssl = new OpenSSLExtension();
		if (empty($encrypted = $openssl->encrypt(base64_encode(serialize($data)), $fingerprint, '1234567890123456'))) {
			return [
				'key'        => 'The BTCPay payment plugin was called to serialize an encrypted object and failed!',
				'domain'     => 'Admin.Catalog.Notification',
				'parameters' => [],
			];
		}

		return $encrypted;
	}

	/**
	 * @return PrivateKey|PublicKey|SinKey|TokenInterface|array
	 */
	public function decrypt(string $data)
	{
		if (empty($data)) {
			return [
				'key'        => 'The BTCPay payment plugin was called to decrypt data but no data was passed!',
				'domain'     => 'Admin.Catalog.Notification',
				'parameters' => [],
			];
		}

		if (40 !== \strlen($fingerprint = sha1(sha1(__DIR__)))) {
			return [
				'key'        => 'Invalid server fingerprint generated!',
				'domain'     => 'Admin.Catalog.Notification',
				'parameters' => [],
			];
		}

		$openssl = new OpenSSLExtension();
		if (false === ($decrypted = base64_decode($openssl->decrypt($data, $fingerprint, '1234567890123456'), true))) {
			return [
				'key'        => 'The BTCPay payment plugin was called to unserialize a decrypted object and failed! The decrypt function was called with "%s"',
				'domain'     => 'Admin.Catalog.Notification',
				'parameters' => [],
			];
		}

		return unserialize($decrypted, [PrivateKey::class, PublicKey::class, SinKey::class, Token::class]);
	}
}
