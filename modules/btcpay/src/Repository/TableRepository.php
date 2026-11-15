<?php

declare(strict_types=1);

namespace BTCPay\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

if (!\defined('_PS_VERSION_')) {
	exit;
}

final class TableRepository
{
	private readonly string $prefix;

	public function __construct(private readonly Connection $connection)
	{
		$this->prefix = \_DB_PREFIX_;
	}

	/**
	 * @return list<array{key: string, parameters: array<mixed>, domain: string}>
	 */
	public function createTables(): array
	{
		$engine = \_MYSQL_ENGINE_;
		$queries = [
			"CREATE TABLE IF NOT EXISTS `{$this->prefix}bitcoin_payment`(
    			`id` int(11) NOT NULL AUTO_INCREMENT,
                `cart_id` int(11) NOT NULL,
                `order_id` int(11),
                `status` varchar(255) NOT NULL,
                `invoice_id` varchar(255),
                `invoice_reference` varchar(255),
                `amount` varchar(255),
                `redirect` varchar(255),
                PRIMARY KEY (`id`),
                UNIQUE KEY `invoice_id` (`invoice_id`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8mb4",
			// Add the webhook delivery tracking table
			"CREATE TABLE IF NOT EXISTS `{$this->prefix}webhook_delivery`(
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `delivery_id` varchar(255) NOT NULL,
                `invoice_id` varchar(255),
                `event_type` varchar(255),
                `date_add` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `delivery_id` (`delivery_id`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8mb4",
		];

		return $this->execute($queries);
	}

	/**
	 * @return list<array{key: string, parameters: array<mixed>, domain: string}>
	 */
	public function dropLegacyColumns(): array
	{
		// Drop columns
		$columns = ['bitcoin_price', 'bitcoin_paid', 'bitcoin_address', 'bitcoin_refund_address', 'rate'];
		$drops = [];
		foreach ($columns as $column) {
			$drops[] = "ALTER TABLE `{$this->prefix}bitcoin_payment` DROP COLUMN IF EXISTS `{$column}`";
		}

		return $this->execute($drops);
	}

	/**
	 * @return list<array{key: string, parameters: array<mixed>, domain: string}>
	 */
	public function convertToUtf8mb4(): array
	{
		return $this->execute([
			"ALTER TABLE `{$this->prefix}bitcoin_payment` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
			"ALTER TABLE `{$this->prefix}webhook_delivery` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
		]);
	}

	/**
	 * @return list<array{key: string, parameters: array<mixed>, domain: string}>
	 */
	public function dropTables(): array
	{
		return $this->execute([
			"DROP TABLE IF EXISTS `{$this->prefix}webhook_delivery`",
			"DROP TABLE IF EXISTS `{$this->prefix}bitcoin_payment`",
		]);
	}

	/**
	 * @param list<string> $queries
	 *
	 * @return list<array{key: string, parameters: array<mixed>, domain: string}>
	 */
	private function execute(array $queries): array
	{
		try {
			foreach ($queries as $query) {
				// Execute query
				$this->connection->executeQuery($query);
			}
		} catch (Exception $exception) {
			return [['key' => \json_encode($exception->getMessage(), \JSON_THROW_ON_ERROR), 'parameters' => [], 'domain' => 'Admin.Modules.Notification']];
		}

		return [];
	}
}
