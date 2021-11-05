# Upgrading from 4.0 to 5.0

Prestashop modules come with an auto-upgrade feature which will be used to upgrade your currently running plugin. This documents serves as a reference for what has been changed.

## Requirements

The module requirements have been changed to:
- You are using PHP 7.3.0 or higher
- Your PrestaShop is version 1.7.7.0 or higher.
- Your BTCPay Server is version 1.3.0 or higher

## Database

We now fetch all invoice and payment data directly from BTCPay Server so the following columns will be removed from `bitcoin_payment` during the upgrade:
- `bitcoin_price`
- `bitcoin_paid`
- `bitcoin_address`
- `bitcoin_refund_address`
- `rate`

## Different PHP client

We switched from [`btcpayserver-php-client`] to the newer and actively supported [`btcpayserver-greenfield-php`]. This means that the old `IPN` has been replaced with an `webhook` based approach.

For you this means that **_you'll have to re-connect_** to your BTCPay Server instance. 

## Invoice

On the front-end side of things we now show the customer how much they have transferred and via what payment.

### Order states

The order states have received a new translation so that they are more correct:
- `Awaiting Bitcoin payment` has been changed to `Awaiting crypto payment`.
- `Waiting for Bitcoin confirmations` has been changed to `Waiting for confirmations`.
- `Bitcoin transaction failed` has been changed to `Crypto transaction failed`.
- `Paid with Bitcoin` has been changed to `Paid with crypto`.

[`btcpayserver-php-client`]: https://github.com/btcpayserver/btcpayserver-php-client
[`btcpayserver-greenfield-php`]: https://github.com/btcpayserver/btcpayserver-greenfield-php
