bitpay/prestashop-plugin
========================

# About
	
+ Bitcoin payment via bitpay.com for PrestaShop.
	
# System Requirements

+ BitPay.com account
+ PrestaShop 1.4+
+ PHP 5+
+ Curl PHP Extension
+ JSON PHP Extension

# Configuration

The current version of prestashop is version 1.5. It is available either by cloning this repository or downloading the .zip or .tgz file from the [releases page](https://github.com/bitpay/prestashop-plugin/releases/tag/v1.5).

<strong>For Prestashop versions 1.5 and older:</strong><br />
1. Upload files to your PrestaShop installation.<br />
2. Go to your PrestaShop administration. Modules -> Payments & Gateways -> "BitPay" click [Install]<br />
3. Go to your PrestaShop administration. Modules -> Payments & Gateways -> "BitPay" click [Configure]<br />
4. Create an API Key in your bitpay account at bitpay.com.<br />
5. Enter your API Key from step 4.
6. Choose "Low" or "Medium" Speed. The High Speed setting is broken.

<strong>For Prestashop versions 1.6 and newer:</strong><br />
1. Upload files to your PrestaShop installation.<br />
2. Go to your PrestaShop administration. Modules -> "BitPay" click [Install]<br />
3. Go to your PrestaShop administration. Modules -> "BitPay" click [Configure]<br />
4. Create an API Key in your bitpay account at bitpay.com.<br />
5. Enter your API Key from step 4.
6. Choose "Low" or "Medium" Speed. The High Speed setting is broken.


# Support

## BitPay Support

* [GitHub Issues](https://github.com/bitpay/prestashop-plugin/issues)
  * Open an issue if you are having issues with this plugin.
* [Support](https://support.bitpay.com)
  * BitPay merchant support documentation

## PrestaShop Support

* [Homepage](http://www.prestashop.com)
* [Documentation](http://doc.prestashop.com/)
* [Support Forums](http://www.prestashop.com/forums/)

# Contribute

To contribute to this project, please fork and submit a pull request.

# License

The MIT License (MIT)

Copyright (c) 2011-2014 BitPay

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
