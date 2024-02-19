<?php

namespace BTCPay\Form\Type;

use BTCPay\Form\Data\Server;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class ServerType extends TranslatorAwareType
{
	public function __construct(TranslatorInterface $translator, array $locales)
	{
		parent::__construct($translator, $locales);
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		/** @var Server $data */
		$data = $builder->getData() ?? Server::create(new Configuration());

		$builder
			->add('host', UrlType::class, [
				'label'    => $this->trans('BTCPay Server URL', 'Modules.Btcpay.Admin'),
				'help'     => $this->trans('The URL/host to your BTCPay Server instance. Make sure your node is reachable from the internet.', 'Modules.Btcpay.Admin'),
				'required' => true,
			])
			->add('apiKey', TextType::class, [
				'label'      => $this->trans('BTCPay Server API key', 'Modules.Btcpay.Admin'),
				'attr'       => [
					'pattern'     => '[a-zA-Z0-9]+',
					'placeholder' => empty($data->getApiKey())
						? $this->trans('Keep blank to be redirected for authentication', 'Modules.Btcpay.Admin')
						: $this->trans('Removing the API key will disconnect your store', 'Modules.Btcpay.Admin'),
				],
				'required'   => false,
				'empty_data' => null,
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(['data_class' => Server::class]);
	}
}
