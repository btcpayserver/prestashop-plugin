<?php

declare(strict_types=1);

namespace PrestaShopBundle\Controller\Admin;

use FakeShopContext;
use PrestaShop\PrestaShop\Core\Context\ShopContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Test double for PrestaShop's PrestaShopAdminController.
 */
class PrestaShopAdminController
{
	public object $container;

	/**
	 * @var list<array{type: string, message: string}>
	 */
	public array $flashes = [];

	public function setContainer(object $container): void
	{
		$this->container = $container;
	}

	/**
	 * @return array<string, string>
	 */
	public static function getSubscribedServices(): array
	{
		return [];
	}

	public function addFlash(string $type, string $message): void
	{
		$this->flashes[] = ['type' => $type, 'message' => $message];
	}

	protected function redirect(string $url, int $status = 302): RedirectResponse
	{
		return new RedirectResponse($url, $status);
	}

	/**
	 * @param array<string, mixed> $parameters
	 */
	protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
	{
		return new RedirectResponse('/' . $route, $status);
	}

	protected function generateSidebarLink(string $section, ?string $title = null): string
	{
		return 'https://docs.example/' . $section;
	}

	/**
	 * @param array<string, mixed> $parameters
	 */
	protected function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
	{
		return $id;
	}

	protected function getShopContext(): ShopContext
	{
		return new FakeShopContext();
	}
}
