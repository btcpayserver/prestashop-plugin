<?php

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2011-2014 BitPay
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

	include(dirname(__FILE__).'/../../config/config.inc.php');
	include(dirname(__FILE__).'/../../header.php');
	include(dirname(__FILE__).'/bitpay.php');

	$handle = fopen('php://input','r');
	$jsonInput = fgets($handle);

	if(function_exists('json_decode'))
		$decoded = json_decode($jsonInput, true);
	else
		$decoded = rmJSONdecode($jsonInput);

	fclose($handle);

	if(isset($decoded['id']))
	{
		// Call BitPay
		$curl   = curl_init('https://bitpay.com/api/invoice/'.$decoded['id']);
		$length = 0;

		$uname  = base64_encode(Configuration::get('bitpay_APIKEY'));
		$header = array(
		              'Content-Type: application/json',
		              'Content-Length: ' . $length,
		              'Authorization: Basic ' . $uname,
		              'X-BitPay-Plugin-Info: prestashop0.4',
		             );

		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1); // verify certificate
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // check existence of CN and verify that it matches hostname
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);

		$responseString = curl_exec($curl);

		if(!$responseString) {
			$response = curl_error($curl);

			die(Tools::displayError("Error: no data returned from API server!"));

		} else {

			if(function_exists('json_decode'))
			  $response = json_decode($responseString, true);
			else
			  $response = rmJSONdecode($responseString);
		}

		curl_close($curl);

		if($response['error']) {
			bplog($response['error']);

			die(Tools::displayError("Error occurred! (" . $response['error']['type'] . " - " . $response['error']['message'] . ")"));
		} else if(!$response['url']) {
			die(Tools::displayError("Error: Response did not include invoice url!"));
		} else {
			if(function_exists('json_decode'))
				$posData = json_decode($response['posData']);
			else
				$posData = rmJSONdecode($response['posData']);



			if ($posData->hash == crypt($posData->cart_id, Configuration::get('bitpay_APIKEY')))
			{
				$bitpay = new bitpay();

				if (in_array($response['status'], array('paid', 'confirmed', 'complete')))
				{
					if (empty(Context::getContext()->link))
						Context::getContext()->link = new Link(); // workaround a prestashop bug so email is sent
					$key = $posData->key;
					$bitpay->validateOrder($posData->cart_id, Configuration::get('PS_OS_PAYMENT'), $response['price'], $bitpay->displayName, null, array(), null, false, $key);
				}
				$bitpay->writeDetails($bitpay->currentOrder, $posData->cart_id, $response['id'], $response['status']);

			}
			else
			{
				bplog('Hash does not match');
			}
		}


	}
	else
	{
		bplog('Missing invoice ID');
	}


