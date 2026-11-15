<?php

declare(strict_types=1);

class OrderInvoice
{
	public int $id;

	public function __construct(int $id)
	{
		$this->id = $id;
	}
}
