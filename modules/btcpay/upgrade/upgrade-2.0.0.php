<?php
if (!defined('_PS_VERSION_')) {
	exit;
}

function upgrade_module_2_0_0(): bool
{
	// Add invoice reference
	return Db::getInstance()->Execute('ALTER TABLE `' . _DB_PREFIX_ . 'order_bitcoin` ADD invoice_reference varchar(255) AFTER invoice_id');
}
