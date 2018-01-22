# Using the BitPay plugin for Prestashop

## Last Cart Versions Tested: 1.6.1.14 and 1.7.2.4

## Prerequisites
You must have a BitPay merchant account to use this plugin.  It's free to [sign-up for a BitPay merchant account](https://bitpay.com/start).
If you want to test in test mode, please see the following page for more information: https://bitpay.com/docs/testing


## Server Requirements

+ PrestaShop 1.5, 1.6 or 1.7
+ PHP 5+
+ Curl PHP Extension
+ JSON PHP Extension

## Plugin Configuration

### For Prestashop versions 1.5:
1. Upload files to your PrestaShop installation.<br />
2. Go to your PrestaShop administration. Modules -> Payments & Gateways -> "BitPay" click [Install]<br />
3. Go to your PrestaShop administration. Modules -> Payments & Gateways -> "BitPay" click [Configure]<br />
4. Create an API Key in your bitpay account at bitpay.com.<br />
5. Enter your API Key from step 4.
6. Choose "Low" or "Medium" Speed. The High Speed setting is broken.

### For Prestashop versions 1.6 and 1.7:
1. Download the latest release from https://github.com/bitpay/prestashop-plugin/releases
2. Go to your PrestaShop administration. Under "Modules and services" select "Add new module" (v1.6) or "Upload a module" (v1.7)
3. Go to your "installed modules" -> "BitPay" and click [Configure]<br />
4. Create a legacy API Key in your bitpay account at bitpay.com: https://www.bitpay.com/dashboard/merchant/api-keys<br />
5. Enter your API Key from step 4.
6. Choose "Low" or "Medium" Speed. The High Speed setting is broken.
