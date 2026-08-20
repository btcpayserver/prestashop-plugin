# BTCPayServer Plugin - Development Environment

Docker stack for local PrestaShop + BTCPay Server (regtest), with optional ngrok for webhooks and Mailpit for mail.

## TOC

<!-- toc -->

- [Prerequisites](#prerequisites)
- [Getting Started](#getting-started)
- [Make targets](#make-targets)
- [Webhooks (ngrok)](#webhooks-ngrok)
- [Mailpit](#mailpit)
- [Linting](#linting)
- [Autocomplete](#autocomplete)
- [Troubleshooting](#troubleshooting)
- [Directory Structure](#directory-structure)
- [Bitcoin CLI Helper](#bitcoin-cli-helper)
- [License](#license)

<!-- tocstop -->

## Prerequisites

- [Docker](https://docs.docker.com/get-started/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install)
- [Composer](https://getcomposer.org/) (for `make install` / `make build`)
- An [ngrok](https://dashboard.ngrok.com/get-started/setup) authtoken if you need live webhooks

## Getting Started

1. Clone and enter the repo:
   ```sh
   git clone https://github.com/btcpayserver/prestashop-plugin.git
   cd prestashop-plugin
   ```

2. Configure env:
   ```sh
   cp development/.env.dist development/.env
   ```
   Set `PS_VERSION` (see comments in `.env.dist` or [Flashlight tags](https://hub.docker.com/r/prestashop/prestashop-flashlight/tags)). Optionally edit `development/prestashop/prestashop.env`.

3. Start:
   ```sh
   make dev-up
   ```
   For webhooks, use [`make dev-tunnel`](#webhooks-ngrok) instead.

4. Services:
   - Storefront: [http://localhost:8000](http://localhost:8000)
   - Back office: [http://localhost:8000/admin-dev](http://localhost:8000/admin-dev) (`admin@prestashop.com` / `prestashop`)
   - Mailpit: [http://localhost:8026](http://localhost:8026)
   - BTCPay: [http://localhost:8081](http://localhost:8081) (register on first visit)
   - Ngrok inspectors (tunnel profile): [http://localhost:4040](http://localhost:4040) (PrestaShop), [http://localhost:4041](http://localhost:4041) (BTCPay)

5. Install the module: `make build`, then upload `build/btcpay.zip` in **Modules -> Module Manager -> Upload a module**.

   The module is not bind-mounted. After code changes, rebuild and re-upload (or copy files into `/var/www/html/modules/btcpay` in the container).

6. Configure BTCPay under **Payment -> Configure -> BTCPay**:
   - Server URL (from inside Docker): `http://btcpayserver:49392`
   - Create a store + API key with invoice and webhook permissions.

## Make targets

| Target | Purpose |
| --- | --- |
| `make dev-up` | Composer deps + start stack (no ngrok) |
| `make dev-tunnel` | Recreate PrestaShop + ngrok agents; prints public URLs |
| `make dev-down` | Stop stack (including tunnel profile) |
| `make dev-logs` | Follow Compose logs |
| `make dev-reset` | Wipe volumes and start fresh |
| `make install` | Root Composer + module prod deps |
| `make build` | Build `build/btcpay.zip` |
| `make lint` / `make lint-fix` | Check / fix PHP style |

## Webhooks (ngrok)

BTCPay needs a public HTTPS URL to reach PrestaShop.

1. Set `NGROK_AUTHTOKEN` in `development/.env`.
2. Leave `PS_DOMAIN` empty and keep `NGROK_TUNNEL_AUTO_DETECT=http://ngrok:4040` (see `.env.dist`).
3. Start:
   ```sh
   make dev-tunnel
   ```
   Use the printed PrestaShop ngrok URL for storefront/webhook testing. Inspectors: [http://localhost:4040](http://localhost:4040), [http://localhost:4041](http://localhost:4041).

Two ngrok agents run (one tunnel each). Flashlight reads `tunnels[0]` from the PrestaShop agent on `ngrok:4040`.

## Mailpit

SMTP is pointed at Mailpit on start (`init-scripts/001-mailpit-smtp.sh`). UI: [http://localhost:8026](http://localhost:8026) (host port 8026 to avoid clashing with other local Mailpit instances).

## Linting

CI uses PHP 8.0/8.1. Prefer running via Docker if your host PHP is newer:

```sh
docker run --rm -v "$PWD:/app" -w /app php:8.1-cli bash -c 'make lint-fix'
docker run --rm -v "$PWD:/app" -w /app php:8.1-cli bash -c 'make lint'
```

## Autocomplete

`development/autocomplete.php` provides PrestaShop stubs for IDEs.

## Troubleshooting

- Logs: `make dev-logs`
- Shop URL becomes `localhost:800000`: clear `PS_DOMAIN`, keep tunnel auto-detect, then `make dev-tunnel`
- Both ngrok URLs point at BTCPay: `make dev-tunnel` again
- Code changes not visible: rebuild/re-upload the zip (no live mount)
- BTCPay authorize returns to `/security/compromised` or empty POST: log out/in after tunnel start so the admin cookie is `SameSite=None` (set by `init-scripts/002-cookie-samesite.sh`), then authorize again

## Directory Structure

- `.env.dist` -> copy to `.env`
- `docker-compose.yml` / `docker-compose.tunnel.yml`
- `init-scripts/` - Flashlight startup hooks (Mailpit SMTP, cookie SameSite for tunnels)
- `prestashop/`, `btcpayserver/`, `ngrok/` - service config
- `bitcoin-cli.sh` - Bitcoin Core CLI wrapper
- `autocomplete.php` - IDE stubs

## Bitcoin CLI Helper

```sh
./bitcoin-cli.sh COMMAND [ARGS]
```

Runs in the `bitcoind` container (regtest). Useful: `getnewaddress`, `generatetoaddress`, `generate`, `sendtoaddress`, `getbalance`, `listtransactions`.

## License

See [LICENSE](../LICENSE).
