<?php

declare(strict_types=1);

namespace BTCPay\Exception;

use RuntimeException;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class BTCPayException extends RuntimeException
{
}
