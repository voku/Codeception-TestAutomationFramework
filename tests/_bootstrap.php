<?php

// This is global bootstrap for autoloading

define('LOCAL_DB_SERVER', 'localhost');
define('LOCAL_DB_SERVER_USERNAME', 'root');
define('LOCAL_DB_SERVER_PASSWORD', '');
define('LOCAL_DB_DATABASE', 'test');

// move into your application defines
define('YOUR_SYSTEM_NAME', 'FOOBAR');

require_once __DIR__ . '/../src/inc_globals.php';
require_once __DIR__ . '/../src/inc_functions.php';
require_once __DIR__ . '/../thirdparty/composer/autoload.php';
// require_once YOUR_PATH . '/app_bootstrap.php;
