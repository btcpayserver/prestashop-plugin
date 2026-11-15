<?php

declare(strict_types=1);

namespace BTCPay\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

if (!\defined('_PS_VERSION_')) {
	exit;
}

/**
 * @ORM\Entity()
 *
 * @ORM\Table(name="webhook_delivery", uniqueConstraints={@ORM\UniqueConstraint(name="delivery_id", columns={"delivery_id"})})
 */
final class WebhookDelivery
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
	 * @ORM\Column(name="delivery_id", type="string", length=255)
	 */
	private string $deliveryId = '';

	/**
	 * @ORM\Column(name="invoice_id", type="string", length=255, nullable=true)
	 */
	private ?string $invoiceId = null;

	/**
	 * @ORM\Column(name="event_type", type="string", length=255, nullable=true)
	 */
	private ?string $eventType = null;

	/**
	 * @ORM\Column(name="date_add", type="datetime")
	 */
	private ?DateTimeInterface $dateAdd = null;

	public function getId(): int
	{
		return (int) $this->id;
	}

	public function getDeliveryId(): string
	{
		return $this->deliveryId;
	}

	public function setDeliveryId(string $deliveryId): void
	{
		$this->deliveryId = $deliveryId;
	}

	public function getInvoiceId(): ?string
	{
		return $this->invoiceId;
	}

	public function setInvoiceId(?string $invoiceId): void
	{
		$this->invoiceId = $invoiceId;
	}

	public function getEventType(): ?string
	{
		return $this->eventType;
	}

	public function setEventType(?string $eventType): void
	{
		$this->eventType = $eventType;
	}

	public function getDateAdd(): ?DateTimeInterface
	{
		return $this->dateAdd;
	}

	public function setDateAdd(DateTimeInterface $dateAdd): void
	{
		$this->dateAdd = $dateAdd;
	}
}
