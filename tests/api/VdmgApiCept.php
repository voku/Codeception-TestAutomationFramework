<?php

$env = YOUR_SYSTEM_NAME;
if (!$env) {
    $env = 'local';
}

$env_from_cli = getCliArgument('--env');

echo "Choosing codeception env ...\n";
if (!empty($env_from_cli)) {
    echo "... using env=${env_from_cli} from CLI.\n";
    $env = $env_from_cli;
} else {
    echo "... using env=${env} from YOUR_SYSTEM_NAME.\n";
}

switch ($env) {
    case 'testing-foo':
    case 'testing-foo-non-headless':
        $searchFor = 'View_Foo_ApiCest.php';

        break;
    case 'testing-bar':
    case 'testing-bar-non-headless':
        $searchFor = 'View_Bar_ApiCest.php';

        break;
    case 'local':
    case 'local-headless':
    case 'testing-lall':
    case 'testing-lall-non-headless':
        $searchFor = 'View_Lall_ApiCest.php';

        break;
    default:
        echo "... no codeception env found for ${env}.\n";
        echo "... using env=" . YOUR_SYSTEM_NAME . " from YOUR_SYSTEM_NAME.\n";
        switch (YOUR_SYSTEM_NAME) {
            case 'testing-foo':
            case 'testing-foo-non-headless':
                $searchFor = 'View_Foo_ApiCest.php';

                break;
            case 'testing-bar':
            case 'testing-bar-non-headless':
                $searchFor = 'View_Bar_ApiCest.php';

                break;
            case 'testing-lall':
            case 'testing-lall-non-headless':
                $searchFor = 'View_Lall_ApiCest.php';

                break;
            default:
                $searchFor = 'View_Default_ApiCest.php';

                break;
        }

        break;
}

// search for all test-files
$searchForGlobal = '_GLOBAL_ApiCest.php';
$testViewFiles = array();

$appModuleFiles = listDir('src/modules/');
foreach ($appModuleFiles as $appModuleFile) {
    if (
        str_endswith($appModuleFile, $searchFor)
        ||
        str_endswith($appModuleFile, $searchForGlobal)
    ) {
        $testViewFiles[] = str_replace(__DIR__, '', $appModuleFile);
    }
}

$debug = '';
$debug_from_cli = getCliArgument('--debug');
if ($debug_from_cli) {
    $debug = '--debug';
}

$coverage = '';
$coverage_from_cli = getCliArgument('--coverage');
if ($coverage_from_cli) {
    $coverage = '--coverage --coverage-xml --coverage-html';
}

$codeceptCliCommand = 'thirdparty/codecept_php55.phar ' . $debug . ' ' . $coverage . ' --env="' . $env . '" run api';

foreach ($testViewFiles as $appModuleFilesView) {
    echo "Test-File: " . $appModuleFilesView . "\n";

    ob_start();
    echo shell_exec($codeceptCliCommand . ' ' . escapeshellarg($appModuleFilesView));
    $output = ob_get_contents();
    ob_end_clean();
    echo $output;

    checkTestResult($output, $appModuleFilesView);
}
