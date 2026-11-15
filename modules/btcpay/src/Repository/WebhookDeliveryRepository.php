<?php

declare(strict_types=1);

namespace BTCPay\Repository;

use Doctrine\DBAL\Connection;
use PrestaShopLogger;
use Throwable;

if (!\defined('_PS_VERSION_')) {
	exit;
}

/**
 * Tracks processed webhook deliveries so duplicate deliveries are handled once.
 */
final class WebhookDeliveryRepository
{
	public function __construct(private readonly Connection $connection)
	{
	}

	/**
	 * Atomically claim a delivery ID.
	 *
	 * Returns true when the caller should process the delivery and false when it
	 * was already processed. If tracking is unavailable, it fails open so a
	 * missing table never blocks payment notifications.
	 */
	public function claim(string $deliveryId, string $invoiceId, string $eventType): bool
	{
		if ('' === $deliveryId) {
			return true;
		}

		$sql = \sprintf(
			'INSERT IGNORE INTO `%swebhook_delivery` (`delivery_id`, `invoice_id`, `event_type`, `date_add`) VALUES (:deliveryId, :invoiceId, :eventType, NOW())',
			\_DB_PREFIX_
		);

		try {
			$affected = $this->connection->executeStatement($sql, [
				'deliveryId' => $deliveryId,
				'invoiceId' => $invoiceId,
				'eventType' => $eventType,
			]);

			return 1 === $affected;
		} catch (Throwable $throwable) {
			PrestaShopLogger::addLog(
				\sprintf('[WARNING] Could not track webhook delivery %s, processing it anyway: %s', $deliveryId, $throwable->getMessage()),
				PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING
			);

			return true;
		}
	}

	/**
	 * Release a claimed delivery so BTCPay can retry it after a processing error.
	 */
	public function release(string $deliveryId): void
	{
		if ('' === $deliveryId) {
			return;
		}

		$sql = \sprintf('DELETE FROM `%swebhook_delivery` WHERE `delivery_id` = :deliveryId', \_DB_PREFIX_);

		try {
			$this->connection->executeStatement($sql, ['deliveryId' => $deliveryId]);
		} catch (Throwable $throwable) {
			PrestaShopLogger::addLog(
				\sprintf('[WARNING] Could not release webhook delivery %s: %s', $deliveryId, $throwable->getMessage()),
				PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING
			);
		}
	}
}
