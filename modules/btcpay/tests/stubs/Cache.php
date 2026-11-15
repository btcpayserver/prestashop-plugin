<?php

declare(strict_types=1);

class Cache
{
	/** @var string[] */
	public static array $cleaned = [];

	public static function clean(string $pattern): void
	{
		self::$cleaned[] = $pattern;
	}

	public static function reset(): void
	{
		self::$cleaned = [];
	}
}
