<?php

namespace BTCPay\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class BitcoinPaymentRepository
{
	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var string
	 */
	private $prefix;

	public function __construct(Connection $connection, string $prefix)
	{
		$this->connection = $connection;
		$this->prefix     = $prefix;
	}

	/**
	 * @throws DBALException
	 */
	public function createTables(): array
	{
		$errors = [];
		$engine = _MYSQL_ENGINE_;

		$queries = [
			"CREATE TABLE IF NOT EXISTS `{$this->prefix}bitcoin_payment`(
    			`id` int(11) NOT NULL AUTO_INCREMENT,
                `cart_id` int(11) NOT NULL,
                `id_order` int(11),
                `status` varchar(255) NOT NULL,
                `invoice_id` varchar(255),
                `invoice_reference` varchar(255),
                `amount` varchar(255),
                `btc_price` varchar(255),
                `btc_paid` varchar(255),
                `btc_address` varchar(255),
                `btc_refundaddress` varchar(255),
                `redirect` varchar(255),
                `rate` varchar(255),
                PRIMARY KEY (`id`),
                UNIQUE KEY `invoice_id` (`invoice_id`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
		];

		foreach ($queries as $query) {
			// Execute query
			$statement = $this->connection->executeQuery($query);
			if (0 !== (int) $statement->errorCode()) {
				$errors[] = [
					'key'        => json_encode($statement->errorInfo()),
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				];
			}
		}

		return $errors;
	}

	/**
	 * @throws DBALException
	 */
	public function dropTables(): array
	{
		$errors = [];
		$query  = 'DROP TABLE IF EXISTS `{$this->prefix}bitcoin_payment`';

		// Execute query
		$statement = $this->connection->executeQuery($query);
		if (0 !== (int) $statement->errorCode()) {
			$errors[] = [
				'key'        => json_encode($statement->errorInfo()),
				'parameters' => [],
				'domain'     => 'Admin.Modules.Notification',
			];
		}

		return $errors;
	}
}
