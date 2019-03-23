<?php

// overwrite some config settings via "dev"-config
if (isset($_ENV['APP_STAGE']) && isset($_ENV['APP_STAGE']) === 'dev') {
    require_once __DIR__ . '/inc_config_dev.php';
}

// overwrite some config settings via "production"-config
if (isset($_ENV['APP_STAGE']) && $_ENV['APP_STAGE'] === 'production') {
    require_once __DIR__ . '/inc_config_production.php';
}

require_once __DIR__ . '/inc_functions.php';

// fallback config

default_define('APP_VERSION', '12.0.1');
default_define('APP_DIR', __DIR__);

require_once APP_DIR . '/modules/something/example/SomethingExamplePageView.php';
