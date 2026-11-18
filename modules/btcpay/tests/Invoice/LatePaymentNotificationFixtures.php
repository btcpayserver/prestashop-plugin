<?php

namespace BTCPay\Tests\Invoice;

// phpcs:disable Generic.Files.OneClassPerFile.MultipleFound
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
// Lightweight aliases are confined to each isolated notification test process.
class LatePaymentOrder
{
	public int $id = 42;
	public int $id_shop = 3;
	public int $id_currency = 1;
}

class LatePaymentBitcoinPayment
{
	public function getInvoiceId(): string
	{
		return 'invoice-42';
	}
}

class LatePaymentLanguage
{
	public int $id;
	public string $locale = 'it-IT';

	public function __construct(int $id)
	{
		$this->id = $id;
	}
}

class LatePaymentConfiguration
{
	public static function get($key, $idLang = null, $idShopGroup = null, $idShop = null)
	{
		if (3 !== $idShop) {
			throw new \RuntimeException('Shop scope missing');
		}

		if ('PS_LANG_DEFAULT' !== $key) {
			throw new \RuntimeException('Unexpected configuration lookup');
		}

		return 2;
	}
}

class LatePaymentCurrency
{
	public string $iso_code = 'EUR';

	public static function getCurrencyInstance(int $id): self
	{
		return new self();
	}
}

class LatePaymentMail
{
	public static array $calls = [];

	public static function send(...$arguments): bool
	{
		self::$calls[] = $arguments;

		return true;
	}
}

class LatePaymentMessage
{
	public static array $notes = [];

	public static function addToOrder($shop, $order, string $message): void
	{
		self::$notes[] = $message;
	}
}

class LatePaymentContext
{
	public string $shop = 'test-shop';

	public function getTranslator(): self
	{
		return $this;
	}

	public function trans($message, $parameters, $domain, $locale): string
	{
		if ('it-IT' !== $locale || 'Modules.Btcpay.Admin' !== $domain) {
			throw new \RuntimeException('Wrong translation scope');
		}

		return "Pagamento arrivato dopo l'annullamento: completa o rimborsa a mano.";
	}
}

\class_alias(LatePaymentOrder::class, 'Order');
\class_alias(LatePaymentBitcoinPayment::class, 'BTCPay\Entity\BitcoinPayment');
\class_alias(LatePaymentLanguage::class, 'Language');
\class_alias(LatePaymentConfiguration::class, 'Configuration');
\class_alias(LatePaymentCurrency::class, 'Currency');
\class_alias(LatePaymentMail::class, 'Mail');
\class_alias(LatePaymentMessage::class, 'BTCPay\Factory\CustomerMessage');
