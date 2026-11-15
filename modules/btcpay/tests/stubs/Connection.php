<?php

declare(strict_types=1);

namespace Doctrine\DBAL;

/**
 * Test double for the DBAL connection PrestaShop provides.
 * The module does not depend on doctrine/dbal directly.
 */
class Connection
{
	public function executeStatement(string $sql, array $params = [], array $types = []): int
	{
		return 0;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function fetchAllAssociative(string $query, array $params = [], array $types = []): array
	{
		return [];
	}

	public function fetchOne(string $query, array $params = [], array $types = []): mixed
	{
		return false;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public function insert(string $table, array $data, array $types = []): int
	{
		return 1;
	}
}
