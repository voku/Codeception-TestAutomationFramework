<?php

require_once __DIR__ . '/../inc_globals.php';
require_once __DIR__ . '/../inc_functions.php';
require_once __DIR__ . '/../../thirdparty/composer/autoload.php';

$files = listDir(__DIR__ . '/../modules/');
foreach ($files as $file) {

    if (
        str_endswith($file, 'View.php')
        &&
        str_endswith(str_replace('.php', '', $file), $_GET['view'])
    ) {
        require_once $file;

        echo (new $_GET['view'])->show();
    }
}
