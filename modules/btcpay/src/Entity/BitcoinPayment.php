<?php

namespace BTCPay\Entity;

/**
 * phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
 */
class BitcoinPayment extends \ObjectModel
{
	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var int
	 */
	public $cart_id;

	/**
	 * @var int|null
	 */
	public $order_id;

	/**
	 * @var string
	 */
	public $status;

	/**
	 * @var string|null
	 */
	public $invoice_id;

	/**
	 * @var string|null
	 */
	public $invoice_reference;

	/**
	 * @var string|null
	 */
	public $amount;

	/**
	 * @var string|null
	 */
	public $bitcoin_price;

	/**
	 * @var string|null
	 */
	public $bitcoin_paid;

	/**
	 * @var string|null
	 */
	public $bitcoin_address;

	/**
	 * @var string|null
	 */
	public $bitcoin_refund_address;

	/**
	 * @var string|null
	 */
	public $redirect;

	/**
	 * @var string|null
	 */
	public $rate;

	/**
	 * {@inheritdoc}
	 */
	public static $definition = [
		'table'   => 'bitcoin_payment',
		'primary' => 'id',
		'fields'  => [
			'cart_id'                => ['type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedInt'],
			'order_id'               => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
			'status'                 => ['type' => self::TYPE_STRING, 'required' => true, 'validate' => 'isString'],
			'invoice_id'             => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
			'invoice_reference'      => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
			'amount'                 => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
			'bitcoin_price'          => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
			'bitcoin_paid'           => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
			'bitcoin_address'        => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
			'bitcoin_refund_address' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
			'redirect'               => ['type' => self::TYPE_STRING, 'validate' => 'isUrl'],
			'rate'                   => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
		],
	];

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getCartId(): int
	{
		return $this->cart_id;
	}

	public function setCartId(int $cart_id): void
	{
		$this->cart_id = $cart_id;
	}

	public function getOrderId(): int
	{
		return $this->order_id;
	}

	public function setOrderId(?int $order_id): void
	{
		$this->order_id = $order_id;
	}

	public function getStatus(): string
	{
		return $this->status;
	}

	public function setStatus(string $status): void
	{
		$this->status = $status;
	}

	public function getStatusName(): string
	{
		$name = $this->getStatus();

		if (null !== ($orderState = new \OrderState((int) $name))) {
			if (\is_string($orderState->name)) {
				return $orderState->name;
			}

			if (\is_array($orderState->name) && !empty($orderState->name)) {
				return array_pop($orderState->name);
			}
		}

		return $name;
	}

	public function getInvoiceId(): ?string
	{
		return $this->invoice_id;
	}

	public function setInvoiceId(?string $invoice_id): void
	{
		$this->invoice_id = $invoice_id;
	}

	public function getInvoiceReference(): ?string
	{
		return $this->invoice_reference;
	}

	public function setInvoiceReference(?string $invoice_reference): void
	{
		$this->invoice_reference = $invoice_reference;
	}

	public function getAmount(): ?string
	{
		return $this->amount;
	}

	public function setAmount(?string $amount): void
	{
		$this->amount = $amount;
	}

	public function getBitcoinPrice(): ?string
	{
		return $this->bitcoin_price;
	}

	public function setBitcoinPrice(?string $bitcoin_price): void
	{
		$this->bitcoin_price = $bitcoin_price;
	}

	public function getBitcoinPaid(): ?string
	{
		return $this->bitcoin_paid;
	}

	public function setBitcoinPaid(?string $bitcoin_paid): void
	{
		$this->bitcoin_paid = $bitcoin_paid;
	}

	public function getBitcoinAddress(): ?string
	{
		return $this->bitcoin_address;
	}

	public function setBitcoinAddress(?string $bitcoin_address): void
	{
		$this->bitcoin_address = $bitcoin_address;
	}

	public function getBitcoinRefundAddress(): ?string
	{
		return $this->bitcoin_refund_address;
	}

	public function setBitcoinRefundAddress(?string $bitcoin_refund_address): void
	{
		$this->bitcoin_refund_address = $bitcoin_refund_address;
	}

	public function getRedirect(): ?string
	{
		return $this->redirect;
	}

	public function setRedirect(?string $redirect): void
	{
		$this->redirect = $redirect;
	}

	public function getRate(): ?string
	{
		return $this->rate;
	}

	public function setRate(?string $rate): void
	{
		$this->rate = $rate;
	}

	public function toArray(): array
	{
		return [
			'id'                => $this->getId(),
			'cart_id'           => $this->getCartId(),
			'id_order'          => $this->getOrderId(),
			'status'            => $this->getStatus(),
			'invoice_id'        => $this->getInvoiceId(),
			'invoice_reference' => $this->getInvoiceReference(),
			'amount'            => $this->getAmount(),
			'btc_price'         => $this->getBitcoinPrice(),
			'btc_paid'          => $this->getBitcoinPaid(),
			'btc_address'       => $this->getBitcoinAddress(),
			'btc_refundaddress' => $this->getBitcoinRefundAddress(),
			'redirect'          => $this->getRedirect(),
			'rate'              => $this->getRate(),
		];
	}
}
