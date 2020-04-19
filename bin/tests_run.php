#!/usr/bin/env php
<?php

require __DIR__ . '/../src/inc_functions.php';
require __DIR__ . '/../thirdparty/composer/autoload.php';

$output = array();
exec('[ -n "$SSH_CLIENT" ] || [ -n "$SSH_TTY" ] && echo "remote" || echo "local"', $output);
if ($output[0] != 'local') {
    echo "Run this script only on your computer!\n\n";
    exit(1);
}

$output = array();
exec('which chromium-browser', $output);
if (!isset($output[0]) || !$output[0]) {
    echo "Install chromium-browser\n";
    echo "command: sudo apt-get install chromium-browser\n\n";
    exit(1);
}

if (!extension_loaded('curl')) {
    echo "Install php-curl and curl\n";
    echo "command: sudo apt-get install php-curl php7.x-curl curl\n\n";
    exit(1);
}

$chrome_driver = '';
if (file_exists('/usr/local/bin/chromedriver')) {
    $chrome_driver = '/usr/local/bin/chromedriver';
} elseif (file_exists('/usr/lib/chromium-browser/chromedriver')) {
    $chrome_driver = '/usr/lib/chromium-browser/chromedriver';
}

if (!$chrome_driver) {
    echo "Install chromium-chromedriver\n";
    echo "command: sudo apt-get install chromium-chromedriver\n\n";
    exit(1);
}

/**
 * @param string $dir
 * @param string $searchFile
 *
 * @return array
 */
function listDirForTests($dir, $searchFile = '') {
    if (!$searchFile) {
        return array();
    }

    $files = array();
    $it = new RecursiveDirectoryIterator($dir);

    foreach ($it as $fileobj) {
        $filename = "{$fileobj}";
        if ($it->hasChildren()) {
            $files = array_merge($files, listDirForTests($filename, $searchFile));
        } else {
            $filenameLower = strtolower($filename);
            $searchFileLower = strtolower($searchFile);
            if (
                substr($filenameLower, -strlen($searchFileLower)) === $searchFileLower
                ||
                substr($filenameLower, -strlen($searchFileLower . '.php')) === $searchFileLower . '.php'
            ) {
                $files[] = $filename;
            }
        }
    }

    asort($files);

    return $files;
}

// init
$test_script = '';
$codecept_options = '';

$test_from_cli = getCliArgument('--test');
if ($test_from_cli) {
    // modules
    $test_file_array = listDirForTests(__DIR__ . '/../src/modules', $test_from_cli);
    if (count($test_file_array) === 1) {
        $test_script = realpath($test_file_array[0]);
        $test_script = str_replace(dirname(__DIR__), '', $test_script);
    }

    if (!$test_script) {
        echo 'Test-File not found: ' . $test_from_cli;
        exit(1);
    }
}

if ($test_script) {
    if (strpos($test_script, 'AcceptanceCest') !== false) {
        $codecept_options = ' acceptance ' . escapeshellarg($test_script);
    } elseif (strpos($test_script, 'UnitCest') !== false) {
        $codecept_options = ' unit ' . escapeshellarg($test_script);
    } elseif (strpos($test_script, 'ApiCest') !== false) {
        $codecept_options = ' api ' . escapeshellarg($test_script);
    }
}

$env_from_cli = getCliArgument('--env');
// fallback
if (!$env_from_cli) {
    $env_from_cli = 'local-headless';
}

$debug = '--debug';
$chrome_driver_log_level = '';
$debug_from_cli = getCliArgument('--debug');
if ($debug_from_cli) {
    $chrome_driver_log_level = ' --log-level=INFO ';
}

$coverage = '';
$coverage_cli_extra = '';
$coverage_cli_php_server = '';
$coverage_from_cli = getCliArgument('--coverage');
if ($coverage_from_cli) {
    $coverage = '--coverage --coverage-xml --coverage-html';
    $coverage_cli_extra = ' php -d"xdebug.remote_enable=1" -d"xdebug_remote_autostart=1" ';
    $coverage_cli_php_server = '(cd ' . __DIR__ . '/../src/public && php -S localhost:8888 index_wrapper.php > /dev/null 2>&1 &);';
}

$runCommand = ' \
    (cd ' . __DIR__ . '/../src/public && php -S localhost:8887 index.php > /dev/null 2>&1 &);
    pkill chromedriver; \
    pkill chromium; \
    sleep 1; \
    ' . $coverage_cli_php_server . '
    (' . $chrome_driver . ' --url-base=/wd/hub ' . $chrome_driver_log_level . ' > /dev/null 2>&1 &); \
    sleep 1';

if ($debug) {
    echo "\n\n" . $runCommand . "\n\n";
}

passthru($runCommand);

$codeceptCliCommand = 'cd ' . __DIR__ . '/../ && ' . $coverage_cli_extra . ' ' . 'thirdparty/codecept.phar ' . $debug . ' ' . $coverage . ' --fail-fast --env="' . $env_from_cli . '" run ' . $codecept_options;

if ($debug) {
    echo "\n\n" . $codeceptCliCommand . "\n\n";
}

passthru('bash -c "' . $codeceptCliCommand . '"');
