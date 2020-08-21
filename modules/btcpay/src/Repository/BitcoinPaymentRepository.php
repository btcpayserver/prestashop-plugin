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
	 * phpcs:disable Generic.Files.LineLength.MaxExceeded
	 *
	 * @throws DBALException
	 */
	public function installOrderStates(string $moduleName): array
	{
		$errors = [];

		// Start a transaction so we don't fuck up the database if shit goes wrong
		$this->connection->beginTransaction();

		$queries = [
			// Insert awaiting payment state
			"INSERT INTO  `ps_order_state_lang` (`id_order_state`,`id_lang`,`name`,`template`) VALUES ('39','1','Awaiting Bitcoin payment', '');",
			"INSERT INTO `ps_order_state` (`id_order_state`, `invoice`, `send_email`, `module_name`, `color`, `unremovable`, `hidden`, `logable`, `delivery`, `shipped`, `paid`, `pdf_invoice`, `pdf_delivery`, `deleted`) VALUES ('39', '0', '0', '" . $moduleName . "', '#FF8C00', '1', '0', '0', '0', '0', '0', '0', '0', '0');",

			// Insert awaiting confirmations state
			"INSERT INTO `ps_order_state_lang` (`id_order_state`,`id_lang`,`name`,`template`) VALUES ('40','1','Waiting for Bitcoin confirmations', '');",
			"INSERT INTO `ps_order_state` (`id_order_state`, `invoice`, `send_email`, `module_name`, `color`, `unremovable`, `hidden`, `logable`, `delivery`, `shipped`, `paid`, `pdf_invoice`, `pdf_delivery`, `deleted`) VALUES ('40', '0', '0', '" . $moduleName . "', '#4169E1', '1', '0', '0', '0', '0', '0', '0', '0', '0');",

			// Insert transaction failed state
			"INSERT INTO `ps_order_state_lang` (`id_order_state`,`id_lang`,`name`,`template`) VALUES ('41','1','Bitcoin transaction failed','payment_error');",
			"INSERT INTO `ps_order_state` (`id_order_state`, `invoice`, `send_email`, `module_name`, `color`, `unremovable`, `hidden`, `logable`, `delivery`, `shipped`, `paid`, `pdf_invoice`, `pdf_delivery`, `deleted`) VALUES ('41', '0', '0', '" . $moduleName . "', '#EC2E15', '1', '0', '1', '0', '0', '0', '0', '0', '0');",

			// Insert payment succeeded state
			"INSERT INTO `ps_order_state_lang` (`id_order_state`,`id_lang`,`name`,`template`) VALUES ('42','1','Paid with Bitcoin','payment');",
			"INSERT INTO `ps_order_state` (`id_order_state`, `invoice`, `send_email`, `module_name`, `color`, `unremovable`, `hidden`, `logable`, `delivery`, `shipped`, `paid`, `pdf_invoice`, `pdf_delivery`, `deleted`) VALUES ('42', '0', '1', '" . $moduleName . "', '#108510', '1', '0', '1', '0', '0', '1', '1', '0', '0');",
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

		// Check if we have any errors, so we can rollback
		if (!empty($errors)) {
			// Cancel the transaction
			if (false === $this->connection->rollBack()) {
				$errors[] = [
					'key'        => json_encode($this->connection->errorInfo()),
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				];
			}

			return $errors;
		}

		// Store the made changes in the database
		if (false === $this->connection->commit()) {
			$errors[] = [
				'key'        => json_encode($this->connection->errorInfo()),
				'parameters' => [],
				'domain'     => 'Admin.Modules.Notification',
			];
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
