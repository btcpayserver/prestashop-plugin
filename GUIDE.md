# Using the BTCPay plugin for Prestashop

## Prerequisites
You must have a BTCPay merchant account to use this plugin.

If you want to test the integration, use a BTCPayServer which is running on testnet. A sane default _for testnet_ has been provided.

## Making a release
We currently do not have a working CI, so to create releases you'll have to tag the code, run `make` locally and attach the ZIP file to the release.

## Server Requirements

+ PrestaShop 1.7.6+
+ PHP 7.2+
+ Curl PHP Extension
+ PDO PHP Extension
+ JSON PHP Extension
+ SSL **must** be enabled
+ Be sure your BTCPay server is whitelisted by Prestashop server
+ Be sure your Prestashop server is whitelisted by BTCPay server

## Plugin Configuration

1. Download the latest release from https://github.com/btcpayserver/prestashop-plugin/releases.
2. Go to your PrestaShop backend, under "Modules" select "Upload a module" and upload the ZIP file you downloaded or made. 
3. Go to your "Installed modules" -> "BTCPay" and click \[Configure\].
4. Go to your BTCPay server, select a store, open it's settings and select "Access Tokens".
5. Click on "Create a new token", select your store and then approve.
6. You will see: "Server initiated pairing code: XXXX". Go back to prestashop and enter your pairing code.
7. Press save. Prestashop will now attempt to make a connection with your BTCPayServer.
8. If successful, make a test payment.
