<?php

namespace BTCPay\Github;

use Composer\Semver\Comparator;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class Latest
{
	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $tagName;

	/**
	 * @var string
	 */
	private $commit;

	/**
	 * @var string
	 */
	private $url;

	public function __construct(int $id, string $name, string $tagName, string $commit, string $url)
	{
		$this->id      = $id;
		$this->name    = $name;
		$this->tagName = $tagName;
		$this->commit  = $commit;
		$this->url     = $url;
	}

	public static function create(array $data): self
	{
		return new self($data['id'], $data['name'], $data['tag_name'], $data['target_commitish'], $data['html_url']);
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getTagName(): string
	{
		return $this->tagName;
	}

	public function getCommit(): string
	{
		return $this->commit;
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function newer(string $currentVersion): bool
	{
		return Comparator::greaterThan(
			\str_replace('v', '', $this->getTagName()),
			\str_replace('v', '', $currentVersion)
		);
	}
}
