<?php
	include(dirname(__FILE__).'/../../config/config.inc.php');
	include(dirname(__FILE__).'/bitpay.php');

	$handle = fopen('php://input','r');
	$jsonInput = fgets($handle);
	$decoded = json_decode($jsonInput, true);
	fclose($handle);

	$posData = json_decode($decoded['posData']);

	if ($posData->hash == crypt(($posData->id_order . $posData->cart_id), Configuration::get('bitpay_APIKEY')))
	{
		$bitpay = new bitpay();
		$bitpay->writeDetails($posData->id_order, $posData->cart_id, $decoded['id'], $decoded['status']);
	}
?>