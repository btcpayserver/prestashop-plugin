# PrestaShop — AI Context (Root)

Synced from [PrestaShop/PrestaShop](https://github.com/PrestaShop/PrestaShop) branch `9.2.x`.

Last synced: 2026-11

Sync is monthly and manual. Re-read upstream `CONTEXT.md` and `STRUCTURE.md`, then update the generic sections below from those headings. Move the date only after that read. Module-only patterns stay in [Component/Module/CONTEXT.md](Component/Module/CONTEXT.md). When this module's PrestaShop floor moves to a newer branch, the sync target moves with it.

> For folder structure and navigation, see [STRUCTURE.md](STRUCTURE.md).
> Core-only guides (domain index, `src/Core/Domain`, GOTCHAS, MULTISTORE, containers) stay upstream: [9.2.x `.ai/CONTEXT.md`](https://github.com/PrestaShop/PrestaShop/blob/9.2.x/.ai/CONTEXT.md).

## Project overview

PrestaShop is an open-source e-commerce platform built on Symfony. It follows a progressive migration from a legacy architecture (ObjectModel, legacy controllers) toward a modern Domain-Driven Design approach (CQRS, Symfony controllers, Doctrine).

## Branching & Versioning

PrestaShop follows [SemVer](https://semver.org/). Active branches merge upward: `9.2.x` → `develop`. Target the lowest applicable branch.

- **`9.2.x`**: Next release (9.2.0). Bug fixes and improvements go here.
- **`develop`**: Next minor (9.3.0). New features go here. No breaking changes.
- **`8.2.x`**: LTS, security fixes only. Rarely modified.

Breaking changes are only allowed in major versions. See [ADR 0017](https://github.com/PrestaShop/adr/blob/master/0017-backward-compatibility-promise.md) for the backward compatibility promise. More architecture decisions at https://github.com/PrestaShop/adr.

## Architecture layers

| Layer | Location | Role |
|-------|----------|------|
| Core Domain | `src/Core/Domain/` | Business logic: Commands, Queries, Handlers, ValueObjects, Exceptions |
| Core Components | `src/Core/` (non-Domain) | Shared infrastructure: Grid, Form, Hook, Translation, etc. |
| Adapter | `src/Adapter/` | Bridges between Core and legacy code or external systems |
| PrestaShopBundle | `src/PrestaShopBundle/` | Symfony bundle: controllers, form types, Twig extensions, DI config |
| Legacy | `classes/`, `controllers/` | Legacy ObjectModel classes and controllers — do not extend, migrate instead |
| Legacy BO theme | `admin-dev/themes/default/` | Legacy back-office theme for non-migrated pages |
| Admin front-end | `admin-dev/themes/new-theme/` | Back-office UI: Vue.js components, JavaScript, SCSS |
| Front-office themes | `themes/` | Customer-facing Smarty templates |
| Modules | `modules/` | Native and third-party modules |
| Tests | `tests/` | PHPUnit (unit/integration), Behat (behavior), Playwright (UI) |

The domain index for `src/Core/Domain` is the upstream [Domain contexts](https://github.com/PrestaShop/PrestaShop/blob/9.2.x/.ai/CONTEXT.md#domain-contexts) section. This repository does not copy those files.

## Coding standards

- Every PHP file: `declare(strict_types=1);`
- Classes `final` by default; all parameters, return types, and properties must be typed
- No ObjectModel in new code — use Doctrine entities or CQRS commands
- All services defined in YAML; no `new` in controllers
- No `Db::getInstance()` in new code — use Doctrine repositories
- No business logic in controllers — delegate to Handlers
- Catch specific domain exceptions, not generic `\Exception`
- Run `php vendor/bin/php-cs-fixer fix` to apply coding style (config: `.php-cs-fixer.dist.php`)
- Run `php vendor/bin/phpstan analyse` for static analysis (config: `phpstan.neon.dist`)

## CQRS pattern

- **Commands** — write intentions dispatched via `CommandBus`
- **Queries** — read intentions dispatched via `QueryBus`
- **Handlers** — implement logic; never call other handlers (compose at controller level)
- Handler interfaces in `src/Core/Domain/{Domain}/CommandHandler|QueryHandler/`
- Concrete implementations in `src/Adapter/{Domain}/CommandHandler|QueryHandler/`

## Testing

| Type | Framework | Location |
|------|-----------|----------|
| Unit | PHPUnit | `tests/Unit/` |
| Integration | PHPUnit | `tests/Integration/` |
| Behavior | Behat | `tests/Integration/Behaviour/` |
| UI | Playwright | `tests/UI/` |

- **New behavior / bug fix must come with a test.** Add or adjust a test on the class actually touched (unit when the class lends itself to it, integration otherwise); prefer Behat for command/handler behavior; if an existing E2E covers the area, unskip it and re-run rather than leaving a `@todo`/skip in place.
- **Tests never shape the production code.** Do not add a constructor parameter, an injected closure, a widened visibility or a dependency only so a test can reach the code: a seam the production code itself benefits from (an injected clock, a filesystem, a repository interface) is design, a parameter only the test passes is not. A class that is not suited to unit tests (cache clearing, file locks, kernel boot…) gets an integration test instead; when no test is realistic, the PR says why and how the change was verified.

## PR hygiene

Common slips unrelated to the feature itself — check before pushing:

- **No unrelated lock-file changes.** A PR must not carry `composer.lock` / `package-lock.json` updates for dependencies it doesn't intentionally bump (a stray `composer install` often rewrites them). Restore them, or squash them out before review.
- **No IDE / local config files** (`.idea/`, editor settings) and no stray blank-line or missing-end-of-line changes.
- **Keep lists alphabetically sorted** (interface members, imports where applicable, enum-like lists) — not all of this is caught by php-cs-fixer.
- **Squash noise commits** (review fixups, reverts) so history stays readable.
- **Target the lowest applicable branch** (`9.2.x` for bug fixes, `develop` for features) — see Branching & Versioning above.

## Which context to read

Match the task against this table before touching code. A task that mentions a keyword or a path in the second column needs the contexts in the third one. Upstream component files are the PrestaShop 9.2.x copies; this repository does not vendor them.

| Topic | Keywords / paths | Read |
|-------|------------------|------|
| Symfony controllers, routing | `modules/btcpay/src/Controller`, `modules/btcpay/config/routes.yml` | [Controller](https://github.com/PrestaShop/PrestaShop/blob/9.2.x/.ai/Component/Controller/CONTEXT.md), [Router](https://github.com/PrestaShop/PrestaShop/blob/9.2.x/.ai/Component/Router/CONTEXT.md), [Module](Component/Module/CONTEXT.md) |
| Back-office forms | form type, `FormDataProvider`, `modules/btcpay/src/Form` | [Forms](https://github.com/PrestaShop/PrestaShop/blob/9.2.x/.ai/Component/Forms/CONTEXT.md), [Module](Component/Module/CONTEXT.md) |
| Doctrine entities and repositories | `modules/btcpay/src/Entity`, `modules/btcpay/src/Repository` | [Database](https://github.com/PrestaShop/PrestaShop/blob/9.2.x/.ai/Component/Database/CONTEXT.md), [Module](Component/Module/CONTEXT.md) |
| Commands and queries | `AddOrderStateCommand`, `EditOrderStateCommand` | [CQRS](https://github.com/PrestaShop/PrestaShop/blob/9.2.x/.ai/Component/CQRS/CONTEXT.md). Domain index: [upstream](https://github.com/PrestaShop/PrestaShop/blob/9.2.x/.ai/CONTEXT.md#domain-contexts) |
| Payment module | `modules/btcpay` | [Module](Component/Module/CONTEXT.md) |
