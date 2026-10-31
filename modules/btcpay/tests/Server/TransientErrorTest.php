<?php

namespace BTCPay\Tests\Server;

use BTCPay\Server\TransientError;
use BTCPayServer\Exception\BadRequestException;
use BTCPayServer\Exception\BTCPayException;
use BTCPayServer\Exception\ConnectException;
use BTCPayServer\Exception\ForbiddenException;
use BTCPayServer\Exception\RequestException;
use BTCPayServer\Http\Response;
use PHPUnit\Framework\TestCase;

class TransientErrorTest extends TestCase
{
	private const URL = 'https://btcpay.example/api/v1/test';

	/** @dataProvider transientHttpStatuses */
	public function testTemporaryHttpStatusesAreTransient(int $status): void
	{
		$exception = new RequestException('GET', self::URL, new Response($status, 'gateway error', []));

		$this->assertTrue(TransientError::isTransient($exception));
	}

	public function transientHttpStatuses(): array
	{
		return [
			[408], // request timeout
			[429], // rate limited
			[500],
			[502], // gateway behind a restarting BTCPay
			[503],
			[504],
		];
	}

	public function testConnectivityFailuresAreTransient(): void
	{
		$this->assertTrue(TransientError::isTransient(new ConnectException('Connection failed', 7)));
		$this->assertTrue(TransientError::isTransient(new ConnectException('Operation timed out', 28)));
		$this->assertTrue(TransientError::isTransient(new ConnectException('BTCPay Server marked unreachable, request skipped', 0)));
	}

	/** @dataProvider permanentHttpStatuses */
	public function testPermanentHttpStatusesAreNotTransient(int $status): void
	{
		$exception = new RequestException('GET', self::URL, new Response($status, 'error', []));

		$this->assertFalse(TransientError::isTransient($exception));
	}

	public function permanentHttpStatuses(): array
	{
		return [
			[400],
			[401],
			[403],
			[404],
			[409],
			[422],
		];
	}

	public function testSpecificRequestExceptionSubclassesAreNotTransient(): void
	{
		$badRequest     = new BadRequestException('GET', self::URL, new Response(BadRequestException::STATUS, 'error', []));
		$forbidden      = new ForbiddenException('GET', self::URL, new Response(ForbiddenException::STATUS, 'error', []));

		$this->assertFalse(TransientError::isTransient($badRequest));
		$this->assertFalse(TransientError::isTransient($forbidden));
	}

	public function testOtherExceptionsAreNotTransient(): void
	{
		$this->assertFalse(TransientError::isTransient(new BTCPayException('Something else went wrong', 0)));
		$this->assertFalse(TransientError::isTransient(new \RuntimeException('Unrelated failure')));
	}
}
