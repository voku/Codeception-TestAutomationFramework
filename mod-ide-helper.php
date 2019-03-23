#!/usr/bin/php
<?php

// init
$str = '';

$_ENV['APP_STAGE'] = 'a';
require_once __DIR__ . '/src/inc_globals.php';

$constants = get_defined_constants(true);

$str .= '<?php' . "\n\n";
$str .= '/* DO NOT INCLUDE THIS FILE IN THE PROJECT, IT IS ONLY A HELPER FOR THE IDE! */' . "\n\n";

$str .= 'namespace PHPSTORM_META {' . "\n\n";
if (isset($constants['user']) && is_array($constants['user'])) {
    foreach ($constants['user'] as $constant => $value) {
        $str .= 'define("' . $constant . '", "' . $value . '");' . "\n";
    }
}
$str .= "\n" . '}';

/* @noinspection FilePutContentsRaceConditionInspection */
file_put_contents(__DIR__ . '/.phpstorm.meta.php', $str);
