<?php

// Minimal PrestaShop constants required by the module's source files.
\define('_PS_VERSION_', '8.1.0');
\define('_DB_PREFIX_', 'ps_');

require \dirname(__DIR__) . '/vendor/autoload.php';

// Test doubles for the PrestaShop globals used by the repository layer.
require_once __DIR__ . '/stubs/Db.php';
require_once __DIR__ . '/stubs/PrestaShopLogger.php';
require_once __DIR__ . '/stubs/functions.php';
