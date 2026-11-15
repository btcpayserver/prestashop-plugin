# Upgrading to 7.0

Module 7.0 runs on PrestaShop 9.2 and PHP 8.4. Shops below that floor cannot install it.

## Requirements

- PHP 8.4 or higher
- PrestaShop 9.2.0 or higher
- BTCPay Server 1.7.0 or higher

## From any earlier module version

Upgrade is one script, `modules/btcpay/upgrade/upgrade-7.0.0.php`. PrestaShop runs it when the module updates from 2.x, 3.x, 4.x, 5.x, or 6.x. The script is safe to run again: it creates missing tables, drops leftover `bitcoin_*` columns, converts the module tables to `utf8mb4`, unregisters the old payment hooks, and aligns the crypto order states.

Install the module from the back office or replace the `modules/btcpay` directory and use the module manager's upgrade action. Shops still on PrestaShop 8, or on PHP older than 8.4, should stay on module 6.x.
