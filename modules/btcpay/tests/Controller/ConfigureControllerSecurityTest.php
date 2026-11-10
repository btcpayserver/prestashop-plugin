<?php

namespace BTCPay\Tests\Controller;

use BTCPay\Controller\Admin\Improve\Payment\ConfigureController;
use PHPUnit\Framework\TestCase;

class ConfigureControllerSecurityTest extends TestCase
{
	private const EXPRESSION = "request.get('_anonymous_controller') or is_granted('update', request.get('_legacy_controller'))";

	public function testValidateApiKeyActionIsProtectedByPs8Annotation(): void
	{
		$method = new \ReflectionMethod(ConfigureController::class, 'validateAPIKeyAction');

		\preg_match('/@AdminSecurity\("([^"]+)"/', (string) $method->getDocComment(), $matches);
		$this->assertArrayHasKey(1, $matches, 'The action must carry the @AdminSecurity annotation');
		$this->assertSame(self::EXPRESSION, $matches[1]);
	}

	public function testValidateApiKeyActionIsProtectedByPs9Attribute(): void
	{
		$method = new \ReflectionMethod(ConfigureController::class, 'validateAPIKeyAction');

		$attributes = $method->getAttributes('PrestaShopBundle\Security\Attribute\AdminSecurity');
		$this->assertCount(1, $attributes, 'The action must carry the PS 9 AdminSecurity attribute');

		$arguments = $attributes[0]->getArguments();
		$this->assertSame(self::EXPRESSION, $arguments[0]);
		$this->assertSame('Access denied.', $arguments['message']);
	}

	public function testAnonymousCallbacksAreCheckedBeforePermissions(): void
	{
		// The carve-out must come first: an anonymous BTCPay callback has no
		// employee in the session, so is_granted('update', ...) would deny it.
		$this->assertStringStartsWith("request.get('_anonymous_controller') or ", self::EXPRESSION);
		$this->assertStringContainsString("is_granted('update', request.get('_legacy_controller'))", self::EXPRESSION);
	}
}
