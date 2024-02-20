<?php

namespace BTCPay\Installer;

use BTCPay\Repository\TableRepository;

class Tables
{
	/**
	 * @var TableRepository
	 */
	private $tableRepository;

	public function __construct(TableRepository $repository)
	{
		$this->tableRepository = $repository;
	}

	/**
	 * @throws \JsonException
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function install(): array
	{
		return $this->tableRepository->createTables();
	}

	/**
	 * @throws \JsonException
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function uninstall(): array
	{
		return $this->tableRepository->dropTables();
	}
}
