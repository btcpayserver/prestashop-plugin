# Prestashop Plugin

[![Maintained](https://img.shields.io/maintenance/yes/2021?style=flat-square)](https://github.com/btcpayserver/prestashop-plugin/pulse)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/btcpayserver/prestashop-plugin/Validate/master?style=flat-square)](https://github.com/btcpayserver/prestashop-plugin/actions)
[![GitHub License](https://img.shields.io/github/license/btcpayserver/prestashop-plugin?color=brightgreen&style=flat-square)](https://github.com/btcpayserver/prestashop-plugin/blob/master/LICENSE)
[![GitHub contributors](https://img.shields.io/github/contributors-anon/btcpayserver/prestashop-plugin?style=flat-square)](https://github.com/btcpayserver/prestashop-plugin/graphs/contributors)

[![PrestaShop module version](https://img.shields.io/badge/module%20version-4.0.2-brightgreen?style=flat-square)](https://github.com/btcpayserver/prestashop-plugin/releases)
[![GitHub release (latest SemVer)](https://img.shields.io/github/v/release/btcpayserver/prestashop-plugin?sort=semver&style=flat-square)](https://github.com/btcpayserver/prestashop-plugin/releases)
[![GitHub all releases](https://img.shields.io/github/downloads/btcpayserver/prestashop-plugin/total?style=flat-square)](https://github.com/btcpayserver/prestashop-plugin/releases)


This is a Bitcoin payment plugin for PrestaShop using BTCPay server. [BTCPay Server](https://btcpayserver.org) is a free and open source server for merchants wanting to accept Bitcoin for their business.

**Warning**: This version should be considered as beta. Use it at your own risk.

## Tested successfully
* Prestashop 1.7.7.x
* BTCPay server v1.0.6.4

**Note**: The legacy version can be found at [prestashop-plugin-legacy](https://github.com/btcpayserver/prestashop-plugin-legacy) and will not recieve any further support at this time.

That said, this module has `v4` as it's module version and includes migration scripts to try and migrate your old module to this new version. The module name has been kept as is, so upgrading should be easy.

## Internals

This plugin support creating orders before or after payment. That said the `before`-mode still requires a callback from the BTCPay server itself so that all relevant data is available during order creation.

## Future improvements
There is a lot of improvements that can still be made:
* Rewrite to use the new [Greenfield Invoice API](https://docs.btcpayserver.org/API/Greenfield/v1/).

## Documentation

Please check out our [official website](https://btcpayserver.org/), our [complete documentation](https://docs.btcpayserver.org/) and [FAQ](https://docs.btcpayserver.org/FAQ/) for more details.

If you have trouble using BTCPay, consider joining [communities listed on official website](https://btcpayserver.org/#communityCTA) to get help from BTCPay community members. Only file [Github issue](https://github.com/btcpayserver/prestashop-plugin/issues) for technical issues you can't resolve through other channels or feature requests you've validated with other members of community.

Main community chat is located on [Mattermost](https://chat.btcpayserver.org/).

### Quick Start Guide

To get up and running with our plugin quickly, see the [PrestaShop Guide on our documentation website](https://docs.btcpayserver.org/PrestaShop/).

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the 
[tags on this repository](https://github.com/BitcoinMitchell/docker-prestashop/tags).

## Contributing

[![Twitter Follow](https://img.shields.io/twitter/follow/btcpayserver?color=brightgreen&label=Follow%20%40BTCPayServer&style=flat-square)](https://twitter.com/btcpayserver)
[![BTCPay Server Community](https://img.shields.io/badge/chat-mattermost-brightgreen?style=flat-square)](https://chat.btcpayserver.org/btcpayserver)

BTCPay is built and maintained entirely by volunteer contributors around the internet. We welcome and appreciate new contributions.

Contributors looking to help out, before opening a pull request, please [create an issue](https://github.com/btcpayserver/prestashop-plugin/issues/new/choose) 
or join [our community chat](https://chat.btcpayserver.org) to get early feedback, discuss best ways to tackle the problem and to ensure there is no work duplication.

## PrestaShop Support

PrestaShop support can be found through it's official channels.

* [Homepage](https://www.prestashop.com)
* [Documentation](https://doc.prestashop.com)
* [Support Forums](https://www.prestashop.com/forums)

## License

BTCPay Server software, logo and designs are provided under [MIT License](LICENSE).
