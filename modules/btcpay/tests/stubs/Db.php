<?php

/**
 * Test double for PrestaShop's Db class.
 *
 * Simulates the `webhook_delivery` table in memory, with INSERT IGNORE
 * semantics (a duplicate delivery_id is silently ignored and affects no rows).
 */
class Db
{
	/** @var string[] every SQL statement that was executed */
	public array $queries = [];

	/** When true, every query throws, simulating an unavailable database. */
	public bool $unavailable = false;

	/** @var string[] delivery ids currently stored in the fake table */
	private array $deliveryIds = [];

	private int $affectedRows = 0;

	private static ?Db $instance = null;

	public static function getInstance(): self
	{
		return self::$instance ??= new self();
	}

	/**
	 * @throws \RuntimeException when the fake database is marked unavailable
	 */
	public function execute(string $sql): bool
	{
		$this->queries[] = $sql;

		if ($this->unavailable) {
			throw new \RuntimeException('database unavailable');
		}

		if (\preg_match("~INSERT IGNORE INTO `\w+webhook_delivery`.*VALUES \('([^']*)'~s", $sql, $m)) {
			$this->affectedRows = \in_array($m[1], $this->deliveryIds, true) ? 0 : 1;
			$this->deliveryIds[] = $m[1];

			return true;
		}

		if (\preg_match("~DELETE FROM `\w+webhook_delivery` WHERE `delivery_id` = '([^']*)'~s", $sql, $m)) {
			$this->deliveryIds = \array_values(\array_diff($this->deliveryIds, [$m[1]]));
			$this->affectedRows = 1;

			return true;
		}

		$this->affectedRows = 0;

		return true;
	}

	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- mirrors the real PrestaShop API
	public function Affected_Rows(): int
	{
		return $this->affectedRows;
	}

	public static function reset(): void
	{
		self::$instance = new self();
	}
}
