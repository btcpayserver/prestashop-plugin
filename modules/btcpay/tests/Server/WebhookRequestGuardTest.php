<?php

namespace BTCPay\Tests\Server;

use BTCPay\Server\WebhookRequestGuard;
use PHPUnit\Framework\TestCase;

class WebhookRequestGuardTest extends TestCase
{
	public function testAcceptsJsonContentTypeWithParameters(): void
	{
		$this->assertTrue(WebhookRequestGuard::hasJsonContentType('application/json'));
		$this->assertTrue(WebhookRequestGuard::hasJsonContentType('application/json; charset=utf-8'));
		$this->assertTrue(WebhookRequestGuard::hasJsonContentType('Application/JSON;charset=UTF-8'));
	}

	public function testRejectsNonJsonContentType(): void
	{
		$this->assertFalse(WebhookRequestGuard::hasJsonContentType('text/plain'));
		$this->assertFalse(WebhookRequestGuard::hasJsonContentType('application/x-www-form-urlencoded'));
		$this->assertFalse(WebhookRequestGuard::hasJsonContentType('application/json-patch+json'));
		$this->assertFalse(WebhookRequestGuard::hasJsonContentType(null));
	}

	public function testBodyLimit(): void
	{
		$this->assertTrue(WebhookRequestGuard::isWithinBodyLimit(0));
		$this->assertTrue(WebhookRequestGuard::isWithinBodyLimit(WebhookRequestGuard::MAX_BODY_BYTES));
		$this->assertFalse(WebhookRequestGuard::isWithinBodyLimit(WebhookRequestGuard::MAX_BODY_BYTES + 1));
		$this->assertFalse(WebhookRequestGuard::isWithinBodyLimit(-1));
	}

	public function testOnlyJsonObjectsAreAccepted(): void
	{
		$this->assertTrue(WebhookRequestGuard::isValidJson('{"type":"InvoiceSettled","invoiceId":"abc"}'));
		$this->assertTrue(WebhookRequestGuard::isValidJson('[]'));
		$this->assertFalse(WebhookRequestGuard::isValidJson(''));
		$this->assertFalse(WebhookRequestGuard::isValidJson('{"type":'));
		$this->assertFalse(WebhookRequestGuard::isValidJson('"a string"'));
		$this->assertFalse(WebhookRequestGuard::isValidJson('42'));
	}
}
