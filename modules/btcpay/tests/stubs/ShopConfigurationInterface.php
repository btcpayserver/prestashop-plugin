<?php

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\Configuration;

use PrestaShop\PrestaShop\Core\ConfigurationInterface;

/**
 * Minimal stand-in for the PrestaShop shop configuration interface, so tests
 * never need a PrestaShop installation.
 */
interface ShopConfigurationInterface extends ConfigurationInterface
{
}
