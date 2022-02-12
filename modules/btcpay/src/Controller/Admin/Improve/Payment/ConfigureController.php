<?php

namespace BTCPay\Controller\Admin\Improve\Payment;

use BTCPay\Constants;
use BTCPay\Form\Data\Configuration;
use BTCPay\Server\Client;
use BTCPay\Server\Data\ValidateApiKey;
use BTCPayServer\Client\ApiKey;
use PrestaShop\PrestaShop\Core\Form\FormHandlerInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use PrestaShopBundle\Security\Annotation\ModuleActivated;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @ModuleActivated(moduleName="btcpay", redirectRoute="admin_module_manage")
 */
class ConfigureController extends FrameworkBundleAdminController
{
	/**
	 * @var ValidatorInterface
	 */
	private $validator;

	public function __construct(ValidatorInterface $validator)
	{
		parent::__construct();

		$this->validator = $validator;
	}

	/**
	 * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))", message="Access denied.")
	 *
	 * @throws \Exception
	 */
	public function editAction(Request $request): Response
	{
		if ($this->isInvalidApiKey()) {
			return $this->render('@Modules/btcpay/views/templates/admin/configure.html.twig', [
				'form'          => $this->get('prestashop.module.btcpay.form_handler')->getForm()->createView(),
				'help_link'     => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
				'invalidApiKey' => true,
				'enableSidebar' => true,
			]);
		}

		return $this->render('@Modules/btcpay/views/templates/admin/configure.html.twig', [
			'form'          => $this->get('prestashop.module.btcpay.form_handler')->getForm()->createView(),
			'help_link'     => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
			'storeId'       => $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID),
			'client'        => Client::createFromConfiguration($this->configuration),
			'enableSidebar' => true,
		]);
	}

	/**
	 * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))", message="Access denied.")
	 *
	 * @return RedirectResponse|Response
	 *
	 * @throws \Exception
	 */
	public function editProcessAction(Request $request): Response
	{
		/** @var FormHandlerInterface $formHandler */
		$formHandler = $this->get('prestashop.module.btcpay.form_handler');

		$form = $formHandler->getForm();
		$form->handleRequest($request);

		// Just show the boring configuration field on no submit/invalid form
		if (!$form->isSubmitted() || !$form->isValid()) {
			return $this->render('@Modules/btcpay/views/templates/admin/configure.html.twig', [
				'form'          => $form->createView(),
				'help_link'     => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
				'invalidApiKey' => $this->isInvalidApiKey(),
				'enableSidebar' => true,
			]);
		}

		/** @var Configuration $configuration */
		$configuration = $form->getData()['btcpay'];

		// If there are errors in the form, error out here
		if (0 !== \count($saveErrors = $formHandler->save($form->getData()))) {
			$this->flashErrors($saveErrors);

			return $this->redirectToRoute('admin_btcpay_configure');
		}

		// Get the store name and redirect URL
		$storeName   = $this->getContext()->shop->name;
		$redirectUrl = $request->getSchemeAndHttpHost() . $this->getAdminLink('btcpay', ['route' => 'admin_btcpay_validate'], true);

		// If there is no apiKey, redirect no matter what
		if (empty($apiKey = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_API_KEY))) {
			return $this->redirect(ApiKey::getAuthorizeUrl($configuration->getUrl(), Constants::BTCPAY_PERMISSIONS, $storeName, true, true, $redirectUrl, $storeName));
		}

		// If we have an apiKey, check if it's valid by fetching the storeId
		try {
			$client = new Client($configuration->getUrl(), $apiKey);

			// If we don't have a store ID, abort right away
			if (null === ($storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID))) {
				return $this->redirect(ApiKey::getAuthorizeUrl($configuration->getUrl(), Constants::BTCPAY_PERMISSIONS, $storeName, true, true, $redirectUrl, $storeName));
			}

			// Ensure we have a webhook
			$client->webhook()->ensureWebhook($storeID);
		} catch (\Throwable $e) {
			// Reset BTCPay details
			$this->configuration->set(Constants::CONFIGURATION_BTCPAY_API_KEY, null);
			$this->configuration->set(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID, null);
			$this->configuration->set(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET, null);

			// Redirect away to get proper details
			return $this->redirect(ApiKey::getAuthorizeUrl($configuration->getUrl(), Constants::BTCPAY_PERMISSIONS, $storeName, true, true, $redirectUrl, $storeName));
		}

		// Return home
		return $this->redirectToRoute('admin_btcpay_configure');
	}

	/**
	 * @return RedirectResponse|Response
	 *
	 * @throws \Exception
	 */
	public function validateAction(Request $request): Response
	{
		// If we didn't receive an API key (or have any errors), just return
		$validateRequest = new ValidateApiKey($request->request);
		if (0 !== \count($errors = $this->validator->validate($validateRequest))) {
			foreach ($errors as $error) {
				$this->addFlash('error', $error->getMessage());
			}

			return $this->redirectToRoute('admin_btcpay_configure');
		}

		// Build the client
		$client = new Client($this->configuration->get(Constants::CONFIGURATION_BTCPAY_HOST), $validateRequest->getApiKey());

		// Get the store ID
		$storeId = $validateRequest->getStoreID();

		try {
			// Ensure we have a valid BTCPay Server version
			if (null !== ($info = $client->server()->getInfo()) && \version_compare($info->getVersion(), Constants::MINIMUM_BTCPAY_VERSION, '<')) {
				$this->addFlash('error', \sprintf('BTCPay server version is too low. Expected %s or higher, received %s.', Constants::MINIMUM_BTCPAY_VERSION, $info->getVersion()));

				return $this->redirectToRoute('admin_btcpay_configure');
			}

			// Ensure we have a payment methods setup
			if (empty($client->payment()->getPaymentMethods($storeId))) {
				$this->addFlash('error', \sprintf("This plugin expects a payment method to have been setup for store '%s'.", $client->store()->getStore($storeId)->offsetGet('name')));

				return $this->redirectToRoute('admin_btcpay_configure');
			}

			// Ensure we have a webhook
			$client->webhook()->ensureWebhook($storeId);
		} catch (\Throwable $exception) {
			$this->addFlash('error', \sprintf('BTCPay plugin: %s', $exception->getMessage()));
			\PrestaShopLogger::addLog('[ERROR] An error occurred during setup ' . \print_r($exception, true));

			return $this->redirectToRoute('admin_btcpay_configure');
		}

		// Store the API key and store ID we received
		$this->configuration->set(Constants::CONFIGURATION_BTCPAY_API_KEY, $validateRequest->getApiKey());
		$this->configuration->set(Constants::CONFIGURATION_BTCPAY_STORE_ID, $storeId);

		$this->addFlash('success', 'BTCPay plugin: Linked!');

		return $this->redirectToRoute('admin_btcpay_configure');
	}

	private function isInvalidApiKey(): bool
	{
		try {
			Client::createFromConfiguration($this->configuration)->server()->getInfo();
		} catch (\Throwable $e) {
			return true;
		}

		return false;
	}
}
