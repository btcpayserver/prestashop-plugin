# BTCPay server - Prestashop Plugin

Be warned, this version should be considered as beta. Use it at your own risk.

## Description

A bitcoin payment plugin for PrestaShop using BTCPay server. [BTCPayServer](https://btcpayserver.org/) is a free and open source server for merchants wanting to accept Bitcoin for their business.

## Tested successfully
* Prestashop 1.7.6.x
* BTCPay server v1.0.1 up to v1.0.5.4

## Quick Start Guide

To get up and running with our plugin quickly, see the GUIDE here: https://github.com/btcpayserver/prestashop-plugin/blob/master/GUIDE.md

# Internals

This plugin only support creating orders before and after payment. 

# TODO
There is a lot of improvements that can still be made:
* IPN.php requires a massive refactoring as it's a mess
* Create a docker container for testing
* Enable CI integration with auto builds + releases
* Ensure stats are correctly displayed in prestashop
* Share the same order number BTCPay server and prestashop, or give insight in order details
* Give bitcoin rate in local currency in order details
    * Also give exact time of payment and bitcoin transaction
* Find a way to not override order state numbers currently used in case another plugin use it.
   * e.g: plugin use order state id: 39,40,41,42.  Should use 49,50,51,52 if other plugins use the first one.

# Support

## Contribute

To contribute to this project, please fork and submit a pull request.
* [GitHub Issues](https://github.com/btcpayserver/prestashop-plugin/issues)

## PrestaShop Support

* [Homepage](http://www.prestashop.com)
* [Documentation](http://doc.prestashop.com/)
* [Support Forums](http://www.prestashop.com/forums/)

# License

The MIT License (MIT)

Copyright (c) 2011-2020 BitPay

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
