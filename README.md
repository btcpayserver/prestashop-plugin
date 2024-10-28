# BTCPay Server - Prestashop Plugin

![BTCPay Server](https://raw.githubusercontent.com/btcpayserver/btcpayserver/master/BTCPayServer/wwwroot/img/btc_pay_BG_twitter.png)

<h3 align="center">
  Accept Bitcoin payments within PrestaShop
</h3>

<div align="center">
  <p>
    <a href="https://github.com/btcpayserver/prestashop-plugin/pulse"><img src="https://img.shields.io/maintenance/yes/2024?style=flat-square" alt="Maintained"/></a>
    <a href="https://github.com/btcpayserver/prestashop-plugin/actions"><img src="https://img.shields.io/github/actions/workflow/status/btcpayserver/prestashop-plugin/validate.yml?style=flat-square" alt="GitHub Workflow Status"/></a>
    <a href="https://github.com/btcpayserver/prestashop-plugin/blob/6.x/LICENSE"><img src="https://img.shields.io/github/license/btcpayserver/prestashop-plugin?color=brightgreen&amp;style=flat-square" alt="GitHub License"/></a>
    <a href="https://github.com/btcpayserver/prestashop-plugin#contributing"><img src="https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat-square" alt="PRs are welcome"/></a>
    <a href="https://github.com/btcpayserver/prestashop-plugin/graphs/contributors"><img src="https://img.shields.io/github/contributors-anon/btcpayserver/prestashop-plugin?style=flat-square" alt="GitHub contributors"/></a>
  </p>
  <p>
    <a href="https://github.com/btcpayserver/prestashop-plugin/releases"><img src="https://img.shields.io/github/v/release/btcpayserver/prestashop-plugin?sort=semver&amp;style=flat-square" alt="GitHub release (latest SemVer)"/></a>
    <a href="https://github.com/btcpayserver/prestashop-plugin/releases"><img src="https://img.shields.io/github/downloads/btcpayserver/prestashop-plugin/total?style=flat-square" alt="GitHub all releases"/></a>
  </p>
</div>

<div align="center">
  <sub>"This is lies, my trust in you is broken, I will make you obsolete" 💚
  </a>
</div>
<br/>

<p align="center">
  <a href="https://github.com/btcpayserver/prestashop-plugin/issues/new/choose">Report a bug</a>
  ·
  <a href="https://github.com/btcpayserver/prestashop-plugin/discussions/new/choose">Request a feature</a>
  ·
  <a href="https://docs.btcpayserver.org/FAQ/">FAQ</a>
</p>

---

## 🔧 Requirements

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
- Prestashop v8.0, v8.0.1, v8.0.4, v8.1.0, v8.1.3 and v8.1.4
- BTCPay server v1.7.0, v1.7.3.0, v1.12.5, v1.13.0 and v1.13.5
  - BTCPay server v2 should be supported as of module version v6.2.0.

### Multistore

As of right now the module is **not** compatible with Prestashop's multistore feature. Any help to resolve this is very much welcomed. Please see the relevant [discussion](https://github.com/btcpayserver/prestashop-plugin/discussions/130).

## 📗 Documentation

Please check out our [official website](https://btcpayserver.org/), [complete documentation](https://docs.btcpayserver.org/) and [FAQ](https://docs.btcpayserver.org/FAQ/) for more details.

If you have trouble using BTCPay Server, consider joining [communities listed on the official website](https://btcpayserver.org/#communityCTA) to get help from other contributors. Only create a [GitHub issue](https://github.com/btcpayserver/prestashop-plugin/issues/new/choose) for technical issues you can't resolve through other channels or feature requests you've validated with other members of the community.

## 🚀 Quick Start Guide

To get up and running with our plugin quickly, see the [PrestaShop Guide on our documentation website](https://docs.btcpayserver.org/PrestaShop/).

## 🧑‍💻 Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [releases within this repository](https://github.com/btcpayserver/prestashop-plugin/releases).

## 🤝 Contributing

BTCPay Server is built and maintained entirely by volunteer contributors around the internet. We welcome and appreciate new contributions.

Contributors looking to help out, before opening a pull request, please join [our community chat](https://chat.btcpayserver.org/) or [start a GitHub discussion](https://github.com/btcpayserver/btcpayserver/discussions) to get early feedback, discuss the best ways to tackle the problem, and ensure there is no work duplication.

### 🔧 Development

We recommend using [PrestaShop Kickstarter](https://github.com/PrestaShopCorp/docker-compose-kickstarter) (which supports [ngrok](https://ngrok.com/)) and a dockerized [BTCPay Server instance](https://github.com/btcpayserver/btcpayserver-docker/blob/master/Production/docker-compose.btc.yml) (or [an online testnet instance](https://testnet.demo.btcpayserver.org/)) for testing locally. 

## 🏪 PrestaShop Support

PrestaShop support can be found through its official channels.

* [Documentation](https://docs.prestashop-project.org/v.8-documentation) - contains answers to commonly asked questions and provides instructions for almost everything. Including setup, management, etc.
* [Support Forums](https://www.prestashop.com/forums) - the official Prestashop forum is a good place to find further answers and ask Prestashop related questions.

## 📝 License

BTCPay Server software, logo and designs are provided under [MIT License](LICENSE).
