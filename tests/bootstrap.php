<?php

declare(strict_types=1);

// The bundle has no vendor of its own: its tests borrow the autoloader of a host project.
// HOST_AUTOLOAD points at it, e.g. HOST_AUTOLOAD=/srv/etradeua/shops/inked.com.ua/vendor/autoload.php
$autoload = null;
foreach ([getenv('HOST_AUTOLOAD') ?: '', __DIR__ . '/../vendor/autoload.php'] as $candidate) {
    if ('' !== $candidate && is_file($candidate)) {
        $autoload = $candidate;

        break;
    }
}

if (null === $autoload) {
    fwrite(\STDERR, "Cannot find vendor/autoload.php. Set HOST_AUTOLOAD or run composer install in the bundle.\n");
    exit(1);
}

$loader = require $autoload;
// Prepended, so the code under test wins over the copy of the bundle installed in the host's vendor.
$loader->addPsr4('Tests\\Vldmrk\\SyliusViewedProductsPlugin\\', __DIR__, true);
$loader->addPsr4('Vldmrk\\SyliusViewedProductsPlugin\\', __DIR__ . '/../src', true);
