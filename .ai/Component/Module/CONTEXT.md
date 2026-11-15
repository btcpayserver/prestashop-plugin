# Module

## Purpose

The BTCPay Server payment module for a PrestaShop 9.2 shop. It links a store, offers crypto checkout, and records invoices. It does not implement multistore, the Admin API, or a checkout-process builder.

## Layers

| Layer | Path |
|-------|------|
| Admin configure | `modules/btcpay/src/Controller/Admin` |
| Storefront controllers | `modules/btcpay/controllers/front` |
| Persistence | `modules/btcpay/src/Entity`, `modules/btcpay/src/Repository` |
| Settings forms | `modules/btcpay/src/Form` |
| BTCPay server and invoices | `modules/btcpay/src/Server`, `modules/btcpay/src/Invoice` |
| Install and upgrade | `modules/btcpay/src/Installer`, `modules/btcpay/upgrade` |

## Non-obvious patterns

- Storefront payment, validation, and the webhook stay `ModuleFrontController` classes. They only read the request and call services.
- Doctrine mappings use `@ORM` annotations. PrestaShop 9.2 registers an `AnnotationDriver`, so PHP attributes are not read. Do not add `index.php` under `src/Entity`.
- `_legacy_controller` is the permission key inside `#[AdminSecurity]`. The route default stays, and it matches the tab `class_name`. There is no other permission key on 9.2.
- Order statuses are created with `AddOrderStateCommand` and updated with `EditOrderStateCommand`. Do not write `module_name` through ObjectModel.
- The private order warning is a `CustomerThread` / `CustomerMessage`. PrestaShop 9.2 has no command that appends that note.
- One Page Checkout: the return to the order page adds `step=1` only when `PS_ONE_PAGE_CHECKOUT_ENABLED` is off.
- The cart must belong to the customer before `validateOrder`. The order amount after payment comes from the BTCPay invoice, not `Cart::getOrderTotal()`. The webhook signature is checked before any order change.

## Canonical examples

- `modules/btcpay/src/Controller/Admin/Improve/Payment/ConfigureController.php` — configure page on `PrestaShopAdminController`
- `modules/btcpay/src/Entity/BitcoinPayment.php` — `@ORM` entity, unprefixed table name
- `modules/btcpay/controllers/front/validation.php` — thin front controller
- `modules/btcpay/upgrade/upgrade-7.0.0.php` — single upgrade onto the 7.0 schema

## Related

- [Controller](https://github.com/PrestaShop/PrestaShop/blob/9.2.x/.ai/Component/Controller/CONTEXT.md), [Forms](https://github.com/PrestaShop/PrestaShop/blob/9.2.x/.ai/Component/Forms/CONTEXT.md), [Database](https://github.com/PrestaShop/PrestaShop/blob/9.2.x/.ai/Component/Database/CONTEXT.md)
- `UPGRADE-7.0.md` at the repository root
