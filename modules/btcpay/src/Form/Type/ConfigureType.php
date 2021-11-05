<?php

namespace BTCPay\Form\Type;

use BTCPay\Constants;
use BTCPay\Form\Data\Configuration;
use BTCPayServer\Client\InvoiceCheckoutOptions;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigureType extends TranslatorAwareType
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('url', UrlType::class, ['label' => $this->trans('BTCPay server url', 'Modules.Btcpay.Admin')])
			->add('speed', ChoiceType::class, [
				'choices'    => [
					$this->trans('Low', 'Modules.Btcpay.Admin')    => InvoiceCheckoutOptions::SPEED_LOW,
					$this->trans('Medium', 'Modules.Btcpay.Admin') => InvoiceCheckoutOptions::SPEED_MEDIUM,
					$this->trans('High', 'Modules.Btcpay.Admin')   => InvoiceCheckoutOptions::SPEED_HIGH,
				],
				'label'      => $this->trans('Transaction speed', 'Modules.Btcpay.Admin'),
				'empty_data' => InvoiceCheckoutOptions::SPEED_MEDIUM,
			])
			->add('order_mode', ChoiceType::class, [
				'choices' => [
					$this->trans('Order before payment', 'Modules.Btcpay.Admin') => Constants::ORDER_MODE_BEFORE,
					$this->trans('Order after payment', 'Modules.Btcpay.Admin')  => Constants::ORDER_MODE_AFTER,
				],
				'label'   => $this->trans('Order mode', 'Modules.Btcpay.Admin'),
			]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(['data_class' => Configuration::class]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlockPrefix(): string
	{
		return 'module_btcpay';
	}
}
