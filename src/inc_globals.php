<?php

define('IS_DEBUGMODUS', true);
define('IS_DEVELOPMENT', false);

if (isset($_ENV['APP_STAGE']) && isset($_ENV['APP_STAGE']) === 'a') {
    require_once __DIR__ . '/inc_config_a.php';
}

if (isset($_ENV['APP_STAGE']) && $_ENV['APP_STAGE'] === 'b') {
    require_once __DIR__ . '/inc_config_b.php';
}

require_once __DIR__ . '/inc_functions.php';

// fallback config

default_define('APP_VERSION', '12.0.1');
default_define('APP_DIR', __DIR__);

require_once APP_DIR . '/framework/DebugBarWrapper.php';
require_once APP_DIR . '/framework/ErrorHandlerLib.php';

$GLOBALS['foooobarrrErrorHandler'] = new ErrorHandlerLib();
$GLOBALS['foooobarrrErrorHandler']->register();

require_once APP_DIR . '/modules/something/example/SomethingExamplePageView.php';
