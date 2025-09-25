# BTCPayServer Plugin - Development Environment

This repository provides a development environment for this plugin, including Docker-based services and helper scripts.

## TOC

<!-- toc -->

- [Prerequisites](#prerequisites)
- [Getting Started](#getting-started)
- [Autocomplete](#autocomplete)
- [Troubleshooting](#troubleshooting)
- [Directory Structure](#directory-structure)
- [Bitcoin CLI Helper](#bitcoin-cli-helper)
- [License](#license)

<!-- tocstop -->

## Prerequisites

- [Docker](https://docs.docker.com/get-started/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install) installed

## Getting Started

1. **Clone the repository:**
   ```sh
   git clone https://github.com/btcpayserver/prestashop-plugin.git
   cd prestashop-plugin
   ```

2. **Configure environment variables:**
   - Copy `.env.dist` to `.env` and adjust values as needed:
     ```sh
     cp development/.env.dist development/.env
     # Edit development/.env as required
     ```
   - For PrestaShop, edit `development/prestashop/prestashop.env` if needed.

3. **Choosing PrestaShop Flashlight Version:**
   - The development environment uses the [PrestaShop Flashlight](https://github.com/PrestaShop/prestashop-flashlight) Docker image.
   - You can select the version by setting the `PS_VERSION` variable in your `.env` file.
     - For example options see commented lines in `.env.dist` or [Docker Hub](https://hub.docker.com/r/prestashop/prestashop-flashlight/tags).
     - Adjust the version to match your testing needs.

4. **Start the development environment:**
   ```sh
   cd development
   docker compose up --d
   ```
   This will pull and start all required containers (e.g., PrestaShop, MariaDB, Ngrok).

5. **Access the services:**
   - PrestaShop: [http://localhost:8080](http://localhost:8080) (will redirect you to the proper Ngrok URL)
     - Default username/password, see [prestashop.env](./prestashop/prestashop.env)
   - BTCPay Server: [http://localhost:49392](http://localhost:49392)
      - Check the logs for the proper Ngrok URL.

6. PrestaShop expects the Ngrok URL to stay the same, if it doesn't you have to stop it, remove the volume and recreate it.

## Autocomplete

- `development/autocomplete.php` provides PHP autocompletion for IDEs.

## Troubleshooting

- Ensure all environment variables are set correctly.
- Check Docker logs for errors: `docker compose logs`
- For permission issues, try running Docker as administrator.

## Directory Structure

This folder contains the following files and subfolders:

- `.env.dist`: Example environment variables for development.
- `autocomplete.php`: Helper for IDE autocompletion.
- `bitcoin-cli.sh`: Helper script to interact with Bitcoin Core inside the Docker environment.
- `docker-compose.yml`: Docker Compose file to spin up the development environment.
- `btcpayserver/`: Contains BTCPay Server-specific environment files and configs:
   - `btcpayserver.env`: Environment variables for BTCPay Server container.
-  `ngrok/ngrok.yml`: Contains Ngrok specific configuration.
- `prestashop/`: Contains PrestaShop-specific environment files and configs:
   - `prestashop.env`: Environment variables for PrestaShop container.

## Bitcoin CLI Helper

The `development/bitcoin-cli.sh` script is a helper for interacting with the Bitcoin node running in Docker (default: regtest mode).

It wraps common `bitcoin-cli` commands for development and testing.

**Usage:**

```sh
./bitcoin-cli.sh COMMAND [ARGS]
```

**Supported commands:**
- `getnewaddress` - Generate a new Bitcoin address.
- `generatetoaddress BLOCKS ADDRESS` - Mine blocks to a specific address.
- `generate NUM_BLOCKS` - Mine a number of blocks (regtest only)
- `settxfee FEE` - Set transaction fee per kB
- `sendtoaddress ADDRESS AMOUNT` - Send funds to an address
- `getbalance` - Get wallet balance
- `listtransactions` - List recent transactions

The script automatically runs the commands inside the `bitcoind` Docker container.

## License

See [LICENSE](../LICENSE) for details.
