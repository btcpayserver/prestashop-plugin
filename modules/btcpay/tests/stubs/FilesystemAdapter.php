<?php

namespace Symfony\Component\Cache\Adapter;

/**
 * Minimal stand-in for Symfony's FilesystemAdapter, so classes using it can be
 * constructed in tests without touching the filesystem.
 */
class FilesystemAdapter
{
	public function getItem($key): \Symfony\Component\Cache\CacheItem
	{
		return new \Symfony\Component\Cache\CacheItem();
	}
}
