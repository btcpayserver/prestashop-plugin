<?php

namespace Symfony\Component\Cache;

/**
 * Minimal stand-in for Symfony's CacheItem: never a cache hit.
 */
class CacheItem
{
	public function isHit(): bool
	{
		return false;
	}

	/**
	 * @return mixed
	 */
	public function get()
	{
		return null;
	}
}
