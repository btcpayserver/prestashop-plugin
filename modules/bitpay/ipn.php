<?php
	
	include(dirname(__FILE__).'/../../config/config.inc.php');
	include(dirname(__FILE__).'/../../header.php');
	include(dirname(__FILE__).'/bitpay.php');
		
	$handle = fopen('php://input','r');
	$jsonInput = fgets($handle);
	bplog($jsonInput);
	$decoded = json_decode($jsonInput, true);
	fclose($handle);

	$posData = json_decode($decoded['posData']);
	
	if (substr($posData->hash, 0, 13) == crypt($posData->cart_id, Configuration::get('bitpay_APIKEY')))
	{	
		$bitpay = new bitpay();		
		
		if (in_array($decoded['status'], array('confirmed', 'complete')))
		{
			if (empty(Context::getContext()->link))
				Context::getContext()->link = new Link(); // workaround a prestashop bug so email is sent 
			$sec_key = substr($posData->hash, -32, 32); //store secure key that we appended to end of posData
			$bitpay->validateOrder($posData->cart_id, Configuration::get('PS_OS_PAYMENT'), $decoded['price'], $bitpay->displayName, null, array(), null, false, $sec_key);
		}
		$bitpay->writeDetails($bitpay->currentOrder, $posData->cart_id, $decoded['id'], $decoded['status']);
		
	}
	else 
	{
		bplog('Hash does not match');
		bplog($posData);
	}
