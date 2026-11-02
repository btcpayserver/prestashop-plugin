<?php

namespace BTCPay\Form\Type;

use BTCPay\Constants;
use BTCPay\Form\Data\General;
use BTCPayServer\Client\InvoiceCheckoutOptions;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class GeneralType extends TranslatorAwareType
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('speed', ChoiceType::class, [
				'choices'    => [
					$this->trans('Low', 'Modules.Btcpay.Admin')    => InvoiceCheckoutOptions::SPEED_LOW,
					$this->trans('Medium', 'Modules.Btcpay.Admin') => InvoiceCheckoutOptions::SPEED_MEDIUM,
					$this->trans('High', 'Modules.Btcpay.Admin')   => InvoiceCheckoutOptions::SPEED_HIGH,
				],
				'label'      => $this->trans('Transaction speed', 'Modules.Btcpay.Admin'),
				'help'       => $this->trans('Controls the confirmations required for on-chain payments. High accepts eligible payments with 0 confirmations; BTCPay Server waits for 1 confirmation when a transaction is flagged for Replace-By-Fee (RBF). Unconfirmed payments can still be replaced or double-spent, including via full RBF. Shipping goods or automatically delivering digital products before confirmation risks fulfilling unpaid orders. Choose Medium or Low to require confirmations.', 'Modules.Btcpay.Admin'),
				'empty_data' => InvoiceCheckoutOptions::SPEED_MEDIUM,
			])
			->add('orderMode', ChoiceType::class, [
				'choices'    => [
					$this->trans('Order before payment', 'Modules.Btcpay.Admin') => Constants::ORDER_MODE_BEFORE,
					$this->trans('Order after payment', 'Modules.Btcpay.Admin')  => Constants::ORDER_MODE_AFTER,
				],
				'label'      => $this->trans('Order creation method', 'Modules.Btcpay.Admin'),
				'help'       => $this->trans('Will we create the order as soon as the user gets redirect to BTCPay Server or do we wait for the webhook.', 'Modules.Btcpay.Admin'),
				'empty_data' => Constants::ORDER_MODE_BEFORE,
			])
			->add('protectOrders', ChoiceType::class, [
				'choices'    => [
					$this->trans('Yes', 'Modules.Btcpay.Admin') => true,
					$this->trans('No', 'Modules.Btcpay.Admin')  => false,
				],
				'label'      => $this->trans('Protect order status', 'Modules.Btcpay.Admin'),
				'help'       => $this->trans('Will protect the order status from changing to "failed" if it already has a "paid" order state. This will protect an order from being cancelled via webhook, if it was paid via a different payment gateway.', 'Modules.Btcpay.Admin'),
				'empty_data' => true,
			])
			->add('shareMetadata', ChoiceType::class, [
				'choices'    => [
					$this->trans('Yes', 'Modules.Btcpay.Admin') => true,
					$this->trans('No', 'Modules.Btcpay.Admin')  => false,
				],
				'label'      => $this->trans('Send customer data to BTCPay Server', 'Modules.Btcpay.Admin'),
				'help'       => $this->trans('If you want customer email, address, etc. sent to BTCPay Server enable this option. By default for privacy and GDPR reasons this is disabled.', 'Modules.Btcpay.Admin'),
				'empty_data' => false,
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(['data_class' => General::class]);
	}
}
