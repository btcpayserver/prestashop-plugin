# Upgrading from 6.0 to 7.0

Prestashop modules come with an auto-upgrade feature which will be used to upgrade your currently running plugin. This document serves as a reference for what has been changed.

## Requirements

The module requirements have been changed to:
- You are using PHP 8.4 or higher
- Your PrestaShop is version 9.1 or higher.
- Your BTCPay Server is version 2.4.2 or higher

## Breaking changes

Version 7.0 drops PrestaShop 8 support. The minimum supported PrestaShop version is 9.1.0.

## Upgrade paths

### Module upgrade (6.x on PrestaShop 9.1+)

If you are already running module 6.x on PrestaShop 9.1 or higher, upload the 7.0 release ZIP through the PrestaShop module manager. The auto-upgrade process preserves your existing `bitcoin_payment` rows, custom order states, BTCPay connection settings, and webhook configuration.

### Shop upgrade (PrestaShop 8)

Shops still on PrestaShop 8 must upgrade the shop to PrestaShop 9.1 or higher before installing module 7.x. Module 7.0 cannot be installed on PrestaShop 8.

## What the 7.0 upgrade does

When upgrading from 6.x, PrestaShop runs `upgrade-7.0.0.php` automatically. The script:

- **Unregisters stale hooks** no longer used by the module: `payment`, `displayPaymentEU`, `displayAdminOrderTop`, `displayInvoice`
- **Drops obsolete columns** from `bitcoin_payment` if they still exist from older releases: `bitcoin_price`, `bitcoin_paid`, `bitcoin_address`, `bitcoin_refund_address`, `rate`
- **Adds `currency_iso`** to `bitcoin_payment` so delayed order creation can compare the BTCPay invoice, the stored checkout snapshot, and the current cart. Existing rows keep a NULL currency until the next invoice fetch fills it. The `amount` column was already present; 7.0 starts persisting it through the ObjectModel.
- **Removes legacy configuration keys** from pre-3.0 and pre-5.0 pairing API eras (e.g. `BTCPAY_TOKEN`, lowercase `btcpay_*` keys)
- **Ensures defaults** for `BTCPAY_PROTECT_ORDERS` (`true`) and `BTCPAY_ORDERMODE` (`before_payment`) when missing

Your BTCPay Server connection settings, webhook configuration, `bitcoin_payment` rows, and custom order states are **not** removed.

If you migrated from a very old 6.x install and hooks look wrong after upgrade, use **Reset** in the module manager to re-run hook registration rather than uninstalling (uninstall removes configuration).
