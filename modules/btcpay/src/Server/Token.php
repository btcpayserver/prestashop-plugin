<?php

namespace BTCPay\Server;

use BTCPayServer\Token as BTCPayToken;

class Token extends BTCPayToken
{
	public static function createToken(string $token): self
	{
		return (new self())
			->setToken($token)
			->setFacade('merchant');
	}
}
