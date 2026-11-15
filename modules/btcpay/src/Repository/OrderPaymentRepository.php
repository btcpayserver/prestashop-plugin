<?php

declare(strict_types=1);

namespace BTCPay\Repository;

use Doctrine\DBAL\Connection;
use Order;

if (!\defined('_PS_VERSION_')) {
	exit;
}

final class OrderPaymentRepository
{
	public function __construct(private readonly Connection $connection)
	{
	}

	/**
	 * @return array<string, string> transaction_id => amount
	 */
	public function amountsByTransactionId(Order $order): array
	{
		$rows = $this->connection->fetchAllAssociative(
			'SELECT `transaction_id`, `amount` FROM `' . \_DB_PREFIX_ . 'order_payment` WHERE `order_reference` = :reference',
			['reference' => (string) $order->reference]
		);

		$map = [];
		foreach ($rows as $row) {
			$transactionId = (string) ($row['transaction_id'] ?? '');
			if ('' === $transactionId) {
				continue;
			}

			$map[$transactionId] = (string) ($row['amount'] ?? '');
		}

		return $map;
	}
}
