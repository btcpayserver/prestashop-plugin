# Prestashop Plugin for BTCPay server, an opensource Payment processor

Warning this is an Beta version
Use it at your own risk

## Description

A bitcoin payment plugin for PrestaShop using BTCPay server.
BTCPay Server is a free and open source server for merchants wanting to accept Bitcoin for their business.
The API is compatible with Bitpay service to allow seamless migration.

BTCPay is design to be easy to deploy on container hosting platform like Azure.
and if you want, some companies provide hosting services.

## Quick Start Guide

To get up and running with our plugin quickly, see the GUIDE here: https://github.com/adapp-tech/prestashop-plugin/blob/master/GUIDE.md


# Internals

This plugin only generate Prestashop order and invoice (aka postponed order), when payment is received.
Prestashop design ensure customer is ready to pay, with a checkbox, when he is forwarded to payment processor.


# TODO
Their is still a lot's of place for improvement.
* ~~on/off postponed order~~
* ~~direct configuration for block confirmations~~
* composer with php-bitpay-client
* docker for testing
* travis integration
* check 1.6.X compatibility
* ensure stats are correctly displayed in prestashop
* share the same order number BTCPay server and prestashop, or give insight in order details
* give bitcoin rate in local currency in order details
* still in order details, give exact time of payment and bitcoin transaction
* find a way to not override order state numbers currently used in case another plugin use it.
e.g: plugin use order state id: 39,40,41,42.  Should use 49,50,51,52 if other plugins use the first one.
* refactoring in ipn.php

# Support

## Tested successfully
* Prestashop 1.7.x
* BTCPay server v1.0.1 and v1.0.2

## Contribute

To contribute to this project, please fork and submit a pull request.
* [GitHub Issues](https://github.com/adapptech/prestashop-plugin/issues)

## PrestaShop Support

* [Homepage](http://www.prestashop.com)
* [Documentation](http://doc.prestashop.com/)
* [Support Forums](http://www.prestashop.com/forums/)


# License

The MIT License (MIT)

Copyright (c) 2011-2018 BitPay

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
