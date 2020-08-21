<?php

namespace BTCPay\Controller\Admin\Improve\Payment;

use PrestaShop\PrestaShop\Core\Form\FormHandlerInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use PrestaShopBundle\Security\Annotation\ModuleActivated;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @ModuleActivated(moduleName="btcpay", redirectRoute="admin_module_manage")
 */
class ConfigureController extends FrameworkBundleAdminController
{
	/**
	 * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))", message="Access denied.")
	 *
	 * @throws \Exception
	 */
	public function editAction(Request $request): Response
	{
		return $this->render('@Modules/btcpay/views/templates/admin/configure.html.twig', [
			'form'          => $this->get('prestashop.module.btcpay.form_handler')->getForm()->createView(),
			'help_link'     => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
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
		$form        = $formHandler->getForm();
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$data       = $form->getData();
			$saveErrors = $formHandler->save($data);

			if (0 === \count($saveErrors)) {
				$this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

				return $this->redirectToRoute('admin_btcpay_configure');
			}

			$this->flashErrors($saveErrors);
		}

		return $this->render('@Modules/btcpay/views/templates/admin/configure.html.twig', [
			'form'          => $form->createView(),
			'help_link'     => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
			'enableSidebar' => true,
		]);
	}
}
