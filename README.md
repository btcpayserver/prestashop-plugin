# Prestashop Plugin

[![Maintained](https://img.shields.io/maintenance/yes/2024?style=flat-square)](https://github.com/btcpayserver/prestashop-plugin/pulse)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/btcpayserver/prestashop-plugin/validate.yml?style=flat-square)](https://github.com/btcpayserver/prestashop-plugin/actions)
[![GitHub License](https://img.shields.io/github/license/btcpayserver/prestashop-plugin?color=brightgreen&style=flat-square)](https://github.com/btcpayserver/prestashop-plugin/blob/6.x/LICENSE)
[![](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat-square)](https://github.com/btcpayserver/prestashop-plugin#contributing)
[![GitHub contributors](https://img.shields.io/github/contributors-anon/btcpayserver/prestashop-plugin?style=flat-square)](https://github.com/btcpayserver/prestashop-plugin/graphs/contributors)

[![GitHub release (latest SemVer)](https://img.shields.io/github/v/release/btcpayserver/prestashop-plugin?sort=semver&style=flat-square)](https://github.com/btcpayserver/prestashop-plugin/releases)
[![GitHub all releases](https://img.shields.io/github/downloads/btcpayserver/prestashop-plugin/total?style=flat-square)](https://github.com/btcpayserver/prestashop-plugin/releases)

This is a Bitcoin payment plugin for PrestaShop using BTCPay server. [BTCPay Server](https://btcpayserver.org) is a free and open source server for merchants wanting to accept Bitcoin for their business.

## Requirements

Please ensure that you meet the following requirements before installing this plugin.

- You are using PHP 8.0 or higher
- Your PrestaShop is version 8.0 or higher.
- Your BTCPay Server is version 1.7.0 or higher
- The PDO, curl, gd, intl, json, and mbstring PHP extensions are available
- You have a BTCPay Server, either [self-hosted](https://docs.btcpayserver.org/Deployment/) or [hosted by a third party](https://docs.btcpayserver.org/Deployment/ThirdPartyHosting/)
- [You've a registered account on the instance](https://docs.btcpayserver.org/RegisterAccount)
- [You've a BTCPay store on the instance](https://docs.btcpayserver.org/CreateStore)
- [You've a wallet connected to your store](https://docs.btcpayserver.org/WalletSetup)

### Tested successfully
- Prestashop version 8.0 and 8.0.1
- BTCPay server version 1.7.0 up to 1.7.3.0

## Multistore

As of right now the module is **not** compatible with Prestashop's multistore feature. 

## Documentation

Please check out our [official website](https://btcpayserver.org/), [complete documentation](https://docs.btcpayserver.org/) and [FAQ](https://docs.btcpayserver.org/FAQ/) for more details.

### Quick Start Guide

To get up and running with our plugin quickly, see the [PrestaShop Guide on our documentation website](https://docs.btcpayserver.org/PrestaShop/).

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [releases within this repository](https://github.com/btcpayserver/prestashop-plugin/releases).

## Contributing

[![Twitter Follow](https://img.shields.io/badge/X-Follow%20@BTCPayServer-brightgreen?style=social)](https://twitter.com/btcpayserver)
[![BTCPay Server Community](https://img.shields.io/badge/Chat-Join%20Mattermost-brightgreen?style=social)](https://chat.btcpayserver.org/btcpayserver)

BTCPay is built and maintained entirely by volunteer contributors around the internet. We welcome and appreciate new contributions.

Contributors looking to help out, before opening a pull request, please [create an issue](https://github.com/btcpayserver/prestashop-plugin/issues/new/choose) 
or join [our community chat](https://chat.btcpayserver.org) to get early feedback, discuss best ways to tackle the problem and to ensure there is no work duplication.

## PrestaShop Support

PrestaShop support can be found through its official channels.

* [Homepage](https://www.prestashop.com)
* [Documentation](https://doc.prestashop.com)
* [Support Forums](https://www.prestashop.com/forums)

## License

BTCPay Server software, logo and designs are provided under [MIT License](LICENSE).
