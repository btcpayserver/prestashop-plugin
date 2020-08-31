<?php

namespace BTCPay\Installer;

use BTCPay\Repository\BitcoinPaymentRepository;

class Tables
{
	/**
	 * @var BitcoinPaymentRepository
	 */
	private $repository;

	public function __construct(BitcoinPaymentRepository $repository)
	{
		$this->repository = $repository;
	}

	public function install(): array
	{
		return $this->repository->createTables();
	}

	public function uninstall(): array
	{
		return $this->repository->dropTables();
	}
}
