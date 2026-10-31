<?php

namespace PrestaShopBundle\Controller\Admin;

use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Test double for PrestaShop's FrameworkBundleAdminController: just enough of
 * a parent class for the module's admin controllers to run in tests.
 */
class FrameworkBundleAdminController
{
	public ?ShopConfigurationInterface $testConfiguration = null;

	/**
	 * @var list<array{type: string, message: string}>
	 */
	public array $flashes = [];

	public function addFlash(string $type, $message): void
	{
		$this->flashes[] = ['type' => $type, 'message' => $message];
	}

	protected function getConfiguration(): ShopConfigurationInterface
	{
		if (null === $this->testConfiguration) {
			throw new \LogicException('No configuration was provided for the test.');
		}

		return $this->testConfiguration;
	}

	protected function flashErrors(array $errors): void
	{
		foreach ($errors as $error) {
			$this->addFlash('error', $error);
		}
	}

	protected function redirect(string $url, int $status = 302): RedirectResponse
	{
		return new RedirectResponse($url, $status);
	}

	protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
	{
		return new RedirectResponse('/' . $route, $status);
	}

	protected function getContext(): \FakeShopContext
	{
		return new \FakeShopContext();
	}

	protected function getAdminLink(string $controller, array $parameters = [], bool $ssl = false): string
	{
		return '/admin/' . $controller;
	}
}
