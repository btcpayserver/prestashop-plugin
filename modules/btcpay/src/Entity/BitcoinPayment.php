<?php

declare(strict_types=1);

namespace BTCPay\Entity;

use Doctrine\ORM\Mapping as ORM;
use OrderState;

if (!\defined('_PS_VERSION_')) {
	exit;
}

/**
 * @ORM\Entity()
 *
 * @ORM\Table(name="bitcoin_payment", uniqueConstraints={@ORM\UniqueConstraint(name="invoice_id", columns={"invoice_id"})})
 */
final class BitcoinPayment
{
	/**
	 * @ORM\Id
	 *
	 * @ORM\Column(name="id", type="integer")
	 *
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private ?int $id = null;

	/**
	 * @ORM\Column(name="cart_id", type="integer")
	 */
	private int $cartId = 0;

	/**
	 * @ORM\Column(name="order_id", type="integer", nullable=true)
	 */
	private ?int $orderId = null;

	/**
	 * @ORM\Column(name="status", type="string", length=255)
	 */
	private string $status = '';

	/**
	 * @ORM\Column(name="invoice_id", type="string", length=255, nullable=true)
	 */
	private ?string $invoiceId = null;

	/**
	 * @ORM\Column(name="invoice_reference", type="string", length=255, nullable=true)
	 */
	private ?string $invoiceReference = null;

	/**
	 * @ORM\Column(name="amount", type="string", length=255, nullable=true)
	 */
	private ?string $amount = null;

	/**
	 * @ORM\Column(name="redirect", type="string", length=255, nullable=true)
	 */
	private ?string $redirect = null;

	public function getId(): int
	{
		return (int) $this->id;
	}

	public function getCartId(): int
	{
		return $this->cartId;
	}

	public function setCartId(int $cartId): void
	{
		$this->cartId = $cartId;
	}

	public function getOrderId(): int
	{
		return (int) $this->orderId;
	}

	public function setOrderId(?int $orderId): void
	{
		$this->orderId = $orderId;
	}

	public function hasOrder(): bool
	{
		return false === empty($this->orderId);
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

		if (null !== ($orderState = new OrderState((int) $name))) {
			if (\is_string($orderState->name)) {
				return $orderState->name;
			}

			if (\is_array($orderState->name) && !empty($orderState->name)) {
				return (string) \array_pop($orderState->name);
			}
		}

		return $name;
	}

	public function getInvoiceId(): ?string
	{
		return $this->invoiceId;
	}

	public function setInvoiceId(?string $invoiceId): void
	{
		$this->invoiceId = $invoiceId;
	}

	public function getInvoiceReference(): ?string
	{
		return $this->invoiceReference;
	}

	public function setInvoiceReference(?string $invoiceReference): void
	{
		$this->invoiceReference = $invoiceReference;
	}

	public function getAmount(): ?string
	{
		return $this->amount;
	}

	public function setAmount(?string $amount): void
	{
		$this->amount = $amount;
	}

	public function getRedirect(): ?string
	{
		return $this->redirect;
	}

	public function setRedirect(?string $redirect): void
	{
		$this->redirect = $redirect;
	}

	/**
	 * @return array{id: int, cart_id: int, id_order: int, status: string, invoice_id: ?string, invoice_reference: ?string, amount: ?string, redirect: ?string}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'cart_id' => $this->getCartId(),
			'id_order' => $this->getOrderId(),
			'status' => $this->getStatus(),
			'invoice_id' => $this->getInvoiceId(),
			'invoice_reference' => $this->getInvoiceReference(),
			'amount' => $this->getAmount(),
			'redirect' => $this->getRedirect(),
		];
	}
}
