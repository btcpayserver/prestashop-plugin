<?php

namespace BTCPay\Form\Type;

use BTCPay\Form\Data\Configuration;
use BTCPayServer\Invoice;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
			->add('url', UrlType::class, [
				'label' => $this->trans('BTCPay server url', 'Modules.Btcpay.Admin'),
			])
			->add('transaction_speed', ChoiceType::class, [
				'choices' => [
					$this->trans('Low', 'Modules.Btcpay.Admin')    => Invoice::TRANSACTION_SPEED_LOW,
					$this->trans('Medium', 'Modules.Btcpay.Admin') => Invoice::TRANSACTION_SPEED_MEDIUM,
					$this->trans('High', 'Modules.Btcpay.Admin')   => Invoice::TRANSACTION_SPEED_HIGH,
				],
				'label'   => $this->trans('Transaction speed', 'Modules.Btcpay.Admin'),
			])
			->add('order_mode', ChoiceType::class, [
				'choices' => [
					$this->trans('Order before payment', 'Modules.Btcpay.Admin') => Configuration::ORDER_MODE_BEFORE,
					$this->trans('Order after payment', 'Modules.Btcpay.Admin')  => Configuration::ORDER_MODE_AFTER,
				],
				'label'   => $this->trans('Order mode', 'Modules.Btcpay.Admin'),
			])
			->add('pairing_code', TextType::class, [
				'label'    => $this->trans('Pairing code', 'Modules.Btcpay.Admin'),
				'required' => false,
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
