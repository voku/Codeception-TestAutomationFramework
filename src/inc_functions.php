<?php

/**
 * @param string $name
 * @param mixed  $value
 * @param bool   $case_insensitive
 */
function default_define($name, $value, bool $case_insensitive = false) {
    if (!defined($name)) {
        define($name, $value, $case_insensitive);
    }
}

/**
 * get a argument from the CLI
 *
 * @param string $cli_argument <p>e.g. "--env"</p>
 *
 * @return null|string
 */
function getCliArgument($cli_argument) {
    // init
    $env_from_cli = null;

    // @codingStandardsIgnoreStart
    $options = $_SERVER['argv'];
    // @codingStandardsIgnoreEnd

    foreach ($options as $optionKey => $option) {
        if (strpos($option, $cli_argument) !== false) {
            if (trim($option) === $cli_argument) {
                $env_from_cli = (string)$option;
            } else {
                $env_from_cli = str_replace(array($cli_argument, '"', '='), '', $options[$optionKey]);
            }
        }
    }

    return $env_from_cli;
}

/**
 * Check if a string ends with specified string (non case insensitive).
 *
 * @param string $string <p>The string to search in</p>
 * @param string $suffix <p>The string to search</p>
 *
 * @return bool
 */
function str_endswith($string, $suffix) {
    return \substr($string, -\strlen($suffix)) == $suffix;
}

/**
 * @param string        $dir                <p>file path</p>
 * @param null|string[] $excludeFileEndWith
 *
 * @return array
 */
function listDir($dir, $excludeFileEndWith = null) {
    $list = array();
    $it = new RecursiveDirectoryIterator($dir);

    if ($excludeFileEndWith) {
        foreach ($excludeFileEndWith as $excludeKey => $excludeString) {
            $excludeFileEndWith[$excludeKey] = strtolower($excludeString);
        }
    }

    foreach ($it as $fileobj) {
        $filename = (string)($fileobj);
        if ($it->hasChildren()) {
            $list = array_merge($list, listDir($filename, $excludeFileEndWith));
        } else {
            $filenameLower = strtolower($filename);
            if (str_endswith($filenameLower, '.php') === true) {

                if ($excludeFileEndWith) {
                    $continue = false;
                    foreach ($excludeFileEndWith as $excludeString) {
                        if (str_endswith($filenameLower, $excludeString)) {
                            $continue = true;

                            break;
                        }
                    }
                    if ($continue === true) {
                        continue;
                    }
                }

                $list[] = $filename;
            }
        }
    }

    asort($list);

    return $list;
}

/**
 * @param string $output
 * @param string $info
 * @param bool   $exitOnError
 *
 * @return bool|void
 *                   void => ($exitOnError === true)
 */
function checkTestResult($output, $info, $exitOnError = true) {
    preg_match("/(?<test_ok_warning>OK, but incomplete)|(?:OK \((?<tests_count>\d*)(?:[\D]*)(?<assertions_count>\d*)(?:[\D]*)\))/", $output, $output_array);
    if (
        !isset($output_array['test_ok_warning'])
        &&
        (
            !isset($output_array['tests_count'], $output_array['assertions_count'])
            ||
            $output_array['tests_count'] == 0
            ||
            $output_array['assertions_count'] == 0
        )
    ) {
        if ($exitOnError === true) {
            trigger_error('test-failed: ' . $info . ' | ' . $output, \E_USER_ERROR);
            exit(1);
        }

        echo 'test-failed: ' . $info . ' | ' . $output . "\n";

        return false;
    }

    return false;
}