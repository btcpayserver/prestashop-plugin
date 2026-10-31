<?php

namespace BTCPay\Repository;

if (!\defined('_PS_VERSION_')) {
	exit;
}

/**
 * Tracks processed webhook deliveries so duplicate deliveries are handled once.
 */
class WebhookDeliveryRepository
{
	/**
	 * Atomically claim a delivery ID.
	 *
	 * Returns true when the caller should process the delivery and false when it
	 * was already processed. If tracking is unavailable, it fails open so a
	 * missing table never blocks payment notifications.
	 */
	public static function claim(string $deliveryId, string $invoiceId, string $eventType): bool
	{
		if ('' === $deliveryId) {
			return true;
		}

		$sql = \sprintf(
			"INSERT IGNORE INTO `%swebhook_delivery` (`delivery_id`, `invoice_id`, `event_type`, `date_add`) VALUES ('%s', '%s', '%s', NOW())",
			\_DB_PREFIX_,
			\pSQL($deliveryId),
			\pSQL($invoiceId),
			\pSQL($eventType)
		);

		try {
			if (false === \Db::getInstance()->execute($sql)) {
				return true;
			}

			return 1 === (int) \Db::getInstance()->Affected_Rows();
		} catch (\Throwable $throwable) {
			\PrestaShopLogger::addLog(
				\sprintf('[WARNING] Could not track webhook delivery %s, processing it anyway: %s', $deliveryId, $throwable->getMessage()),
				\PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING
			);

			return true;
		}
	}

	/**
	 * Release a claimed delivery so BTCPay can retry it after a processing error.
	 */
	public static function release(string $deliveryId): void
	{
		if ('' === $deliveryId) {
			return;
		}

		$sql = \sprintf("DELETE FROM `%swebhook_delivery` WHERE `delivery_id` = '%s'", \_DB_PREFIX_, \pSQL($deliveryId));

		try {
			\Db::getInstance()->execute($sql);
		} catch (\Throwable $throwable) {
			\PrestaShopLogger::addLog(
				\sprintf('[WARNING] Could not release webhook delivery %s: %s', $deliveryId, $throwable->getMessage()),
				\PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING
			);
		}
	}
}
