<?php

/**
 * Info: used for testing interaction with "c3.php"
 *
 * -> used in ../../codeception.yml
 */

// #############################################################################
// # Check for CLI-Server usage ...
// #############################################################################

if (\PHP_SAPI !== 'cli-server') {
    echo 'ERROR: only for PHP-CLI-Server';
    exit(255);
}

// #############################################################################
// # Check for non existing file usage ...
// #############################################################################

$filename = preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (is_file(__DIR__ . $filename)) {
    return false;
}

// #############################################################################
// # Code Coverage for tests ...
// #############################################################################

/* @noinspection PhpComposerExtensionStubsInspection */
if (
    extension_loaded('xdebug')
    &&
    function_exists('xdebug_is_enabled')
    &&
    xdebug_is_enabled() === true
) {
    // #############################################################################
    // # Adding c3.php for code coverage during codeception tests
    // #
    // # ref: https://github.com/Codeception/c3
    // #############################################################################

    // Current code coverage:
    // 1. clear the old data: http://localhost:8887:8888/index_wrapper.php/c3/report/clear
    // 2. generate new data: open a view with Xdebug + xdebug.remote_enable = 1 (path: /tests/_output/codecoverage.serialized)
    // 3. convert data into html: http://localhost:8887:8888/index_wrapper.php/c3/report/html
    // 4. get the html in path: /tests/_output/html/index.html

    // Check for code coverage:
    // - auto-activate xdebug via codeception (acceptance-tests)
    // - merge code-coverage for different tests ("c3.php" should do this?)

    //$_SERVER['HTTP_X_CODECEPTION_CODECOVERAGE_DEBUG'] = 1;
    //$_SERVER['HTTP_X_CODECEPTION_CODECOVERAGE'] = 'Acceptance';
    $_SERVER['HTTP_X_CODECEPTION_CODECOVERAGE_CONFIG'] = __DIR__ . '/../../codeception.yml';

    set_time_limit(0);
    ini_set('memory_limit', '2G');

    require_once __DIR__ . '/../../thirdparty/c3.php';
}

// #############################################################################
// # run the application ...
// #############################################################################

require_once __DIR__ . '/index.php';
