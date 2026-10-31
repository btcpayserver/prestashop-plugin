<?php

// Minimal PrestaShop constants required by the module's source files.
\define('_PS_VERSION_', '8.1.0');
\define('_DB_PREFIX_', 'ps_');

require \dirname(__DIR__) . '/vendor/autoload.php';

// Test doubles for the PrestaShop globals used by the repository layer.
require_once __DIR__ . '/stubs/Db.php';
require_once __DIR__ . '/stubs/Cache.php';
require_once __DIR__ . '/stubs/OrderInvoice.php';
require_once __DIR__ . '/stubs/PrestaShopLogger.php';
require_once __DIR__ . '/stubs/FrameworkBundleAdminController.php';
require_once __DIR__ . '/stubs/functions.php';
require_once __DIR__ . '/stubs/ConfigurationInterface.php';
require_once __DIR__ . '/stubs/ShopConfigurationInterface.php';
require_once __DIR__ . '/stubs/Configuration.php';
require_once __DIR__ . '/stubs/Link.php';
require_once __DIR__ . '/stubs/PaymentModule.php';
require_once __DIR__ . '/stubs/FilesystemAdapter.php';
require_once __DIR__ . '/stubs/CacheItem.php';
require_once __DIR__ . '/stubs/FormHandlerInterface.php';
require_once __DIR__ . '/stubs/FormDataProviderInterface.php';
require_once __DIR__ . '/stubs/ValidatorInterface.php';
require_once __DIR__ . '/stubs/Tools.php';
require_once __DIR__ . '/stubs/FakeShop.php';
require_once __DIR__ . '/stubs/FakeShopContext.php';
