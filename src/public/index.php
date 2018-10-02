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

        if (isset($_GET['api']) && $_GET['api'] == 1) {
            echo json_encode(['view' => (new $_GET['view'])->show()]);
        } else {
            echo (new $_GET['view'])->show();
        }
    }
}
