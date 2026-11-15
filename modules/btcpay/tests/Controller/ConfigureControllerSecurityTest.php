<?php

declare(strict_types=1);

namespace BTCPay\Tests\Controller;

use BTCPay\Controller\Admin\Improve\Payment\ConfigureController;
use PHPUnit\Framework\TestCase;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use PrestaShopBundle\Security\Attribute\DemoRestricted;
use ReflectionAttribute;
use ReflectionMethod;

class ConfigureControllerSecurityTest extends TestCase
{
	private const ANONYMOUS = "request.get('_anonymous_controller') or is_granted('update', request.get('_legacy_controller'))";

	public function testAnonymousActionsKeepThePermissionExpression(): void
	{
		foreach (['validateAPIKeyAction', 'completeAuthorizationAction'] as $action) {
			$arguments = $this->attributeArguments($action, AdminSecurity::class);
			$this->assertSame(self::ANONYMOUS, $arguments[0]);
			$this->assertSame('Access denied.', $arguments['message']);
			$this->assertSame([], $this->attributes($action, DemoRestricted::class), 'A demo redirect would replace the callback 403');
		}
	}

	public function testMerchantActionsAreDemoRestricted(): void
	{
		$this->assertSame("is_granted('read', request.get('_legacy_controller'))", $this->attributeArguments('viewAction', AdminSecurity::class)[0]);
		$this->assertSame('admin_module_manage', $this->attributeArguments('viewAction', DemoRestricted::class)['redirectRoute']);
		foreach (['editServerSettingsAction', 'editGeneralSettingsAction'] as $action) {
			$this->assertSame("is_granted('update', request.get('_legacy_controller'))", $this->attributeArguments($action, AdminSecurity::class)[0]);
			$this->assertSame('Access denied.', $this->attributeArguments($action, AdminSecurity::class)['message']);
			$this->assertSame('admin_btcpay_configure', $this->attributeArguments($action, DemoRestricted::class)['redirectRoute']);
		}
	}

	public function testAnonymousCallbacksAreCheckedBeforePermissions(): void
	{
		// The carve-out must come first: an anonymous BTCPay callback has no
		// employee in the session, so is_granted('update', ...) would deny it.
		$this->assertStringStartsWith("request.get('_anonymous_controller') or ", self::ANONYMOUS);
		$this->assertStringContainsString("is_granted('update', request.get('_legacy_controller'))", self::ANONYMOUS);
	}

	/**
	 * @return list<ReflectionAttribute>
	 */
	private function attributes(string $action, string $attribute): array
	{
		return (new ReflectionMethod(ConfigureController::class, $action))->getAttributes($attribute);
	}

	/**
	 * @return array<int|string, mixed>
	 */
	private function attributeArguments(string $action, string $attribute): array
	{
		$attributes = $this->attributes($action, $attribute);
		$this->assertCount(1, $attributes);

		return $attributes[0]->getArguments();
	}
}
