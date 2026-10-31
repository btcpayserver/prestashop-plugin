<?php

namespace BTCPay\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class TableRepository
{
	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var string
	 */
	private $prefix;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
		$this->prefix     = \_DB_PREFIX_;
	}

	/**
	 * @throws \JsonException
	 */
	public function createTables(): array
	{
		$errors = [];
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
                `bitcoin_price` varchar(255),
                `bitcoin_paid` varchar(255),
                `bitcoin_address` varchar(255),
                `bitcoin_refund_address` varchar(255),
                `redirect` varchar(255),
                `rate` varchar(255),
                PRIMARY KEY (`id`),
                UNIQUE KEY `invoice_id` (`invoice_id`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
			"CREATE TABLE IF NOT EXISTS `{$this->prefix}webhook_delivery`(
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `delivery_id` varchar(255) NOT NULL,
                `invoice_id` varchar(255),
                `event_type` varchar(255),
                `date_add` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `delivery_id` (`delivery_id`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
		];

		try {
			foreach ($queries as $query) {
				$this->connection->executeQuery($query);
			}
		} catch (Exception $e) {
			$errors[] = ['key' => \json_encode($e->getMessage(), \JSON_THROW_ON_ERROR), 'parameters' => [], 'domain' => 'Admin.Modules.Notification'];
		}

		return $errors;
	}

	/**
	 * @throws \JsonException
	 */
	public function dropTables(): array
	{
		$errors = [];
		$queries = [
			"DROP TABLE IF EXISTS `{$this->prefix}webhook_delivery`",
			"DROP TABLE IF EXISTS `{$this->prefix}bitcoin_payment`",
		];

		try {
			foreach ($queries as $query) {
				$this->connection->executeQuery($query);
			}
		} catch (Exception $e) {
			$errors[] = ['key' => \json_encode($e->getMessage(), \JSON_THROW_ON_ERROR), 'parameters' => [], 'domain' => 'Admin.Modules.Notification'];
		}

		return $errors;
	}
}
