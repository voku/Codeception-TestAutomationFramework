<?php

final class ErrorHandlerLib {

    /**
     * helper for out-of-memory errors ...
     *
     * @var null|string
     */
    private static $reservedMemory;

    /**
     * @var null|callable
     */
    private $old_exception_handler;

    /**
     * @var null|callable
     */
    private $old_error_handler;

    /**
     * @var mixed
     */
    private $fatal_error_types = [
        \E_ERROR,
        \E_PARSE,
        \E_CORE_ERROR,
        \E_CORE_WARNING,
        \E_COMPILE_ERROR,
        \E_COMPILE_WARNING,
        \E_STRICT,
    ];

    /**
     * @var null|\Error|\Exception|\Throwable
     */
    private $lastHandledException;

    /**
     * constructor.
     */
    public function __construct() {
        $this->fatal_error_types = array_reduce(
            $this->fatal_error_types,
            [$this, 'bitwiseOr']
        );
    }

    /**
     * @param int $a
     * @param int $b
     *
     * @return int
     */
    private function bitwiseOr($a, $b) {
        return $a | $b;
    }

    /**
     * @param \Error|\Exception|\Throwable $exception
     * @param array<mixed>                 $context
     *
     * @return void
     */
    public function handleException($exception, $context = []) {

        $this->lastHandledException = $exception;

        $this->handleError(
            \E_ERROR,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $context,
            $exception->getTrace()
        );
    }

    /**
     * @param int          $errno
     * @param string       $errstr
     * @param string       $errfile
     * @param int          $errline
     * @param mixed        $context
     * @param array<mixed> $backtrace
     *
     * @return bool|void
     */
    public function handleError(
        $errno,
        $errstr,
        $errfile = '',
        $errline = 0,
        $context = [],
        $backtrace = null
    ) {
        global $Brand;

        static $noticeCounter = 0;
        static $warningCounter = 0;

        // http://php.net/set_error_handler
        //
        // The following error types cannot be handled with a user defined function: E_ERROR,
        // E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, and
        // most of E_STRICT raised in the file where set_error_handler() is called.
        //
        // @see $this->handleFatalError()

        if (
            class_exists('DebugBarWrapper')
            &&
            DebugBarWrapper::isDebugRequest()
        ) {
            static $COUNT_ERROR_CURRENT = null;
            if ($COUNT_ERROR_CURRENT === null) {
                $COUNT_ERROR_CURRENT = 0;
            } else {
                $COUNT_ERROR_CURRENT++;
            }

            if ($COUNT_ERROR_CURRENT <= 50) { // show max 50 warnings, errors, ... in the "DebugBar"

                $debugbar = DebugBarWrapper::getInstance()->getDebugBar();
                if ($debugbar) {
                    $desc = 'ErrorNo:' . $errno . ' Error:' . $errstr . " File:" . $errfile . " Line:" . $errline . "\n\n";

                    if ($backtrace === null) {
                        // @codingStandardsIgnoreStart
                        $backtrace = debug_backtrace();
                        // @codingStandardsIgnoreEnd
                    }
                    $desc .= $this->backtrace($backtrace);

                    if (!empty($context)) {
                        $desc .= "\n" . print_r($context, true);
                    }

                    if (is_ajax_request()) {
                        $desc = htmlentities($desc, ENT_QUOTES);
                    }

                    // TODO? -> hack for codeception: https://github.com/Codeception/Codeception/issues/5896
                    if ($errno === \E_USER_DEPRECATED) {
                        throw new Exception($this->backtrace($backtrace) . $errstr);
                    }

                    $debugbarLog = $debugbar['php_error'];
                    assert($debugbarLog instanceof \DebugBar\DataCollector\MessagesCollector);

                    switch ($errno) {

                        case \E_WARNING:
                        case \E_CORE_WARNING:
                        case \E_COMPILE_WARNING:
                        case \E_USER_WARNING:
                            $debugbarLog->warning($desc);

                            break;

                        case \E_PARSE:
                        case \E_ERROR:
                        case \E_CORE_ERROR:
                        case \E_COMPILE_ERROR:
                        case \E_USER_ERROR:
                        case \E_RECOVERABLE_ERROR:
                            $debugbarLog->error($desc);

                            break;

                        case \E_STRICT:
                        case \E_DEPRECATED:
                        case \E_USER_DEPRECATED:
                        case \E_USER_NOTICE:
                        case \E_NOTICE:
                        default:
                            $debugbarLog->info($desc);

                            break;
                    }
                }
            }
        }

        if ($errno & (\E_ALL ^ (\E_NOTICE | \E_USER_NOTICE | \E_WARNING | \E_USER_WARNING | \E_DEPRECATED | \E_USER_DEPRECATED | \E_STRICT))) {

            // wenn der fehler keine E_NOTICE oder E_WARNING oder die anderen sind || used for e.g. E_USER_ERROR

            if (defined('PAGE_PARSE_START_TIME')) {
                $PageParseStartStr = date('H:i:s', (int)PAGE_PARSE_START_TIME);
            } else {
                $PageParseStartStr = 'Unknown';
            }

            $errorStr = 'Start:' . $PageParseStartStr . ' ErrorNo:' . $errno . ' Error:' . $errstr . " File:" . $errfile . " Line:" . $errline . "\n\n";

            $str = '';
            $str .= $errorStr;
            //$str .= getLoginInfos();
            $str .= $errorStr;

            if ($backtrace === null) {
                // @codingStandardsIgnoreStart
                $backtrace = debug_backtrace();
                // @codingStandardsIgnoreEnd
            }
            $str .= $this->backtrace($backtrace);

            if (!empty($context)) {
                $str .= "\n" . print_r($context, true);
            }

            /* @noinspection ForgottenDebugOutputInspection */
            error_log($str);

            if (is_cli()) {

                // @codingStandardsIgnoreStart
                /** @noinspection ForgottenDebugOutputInspection */
                print_r($str);
                // @codingStandardsIgnoreEnd

                // @codingStandardsIgnoreStart
                exit(1);
                // @codingStandardsIgnoreEnd
            }

            if (IS_DEBUGMODUS) {

                http_response_code(500);
                echo nl2br($str);

            } elseif (is_ajax_request()) {

                http_response_code(500);
                $errorCustomerText = '
                <p class="errorDescription">
                    <strong>Es ist ein unerwarteter Fehler aufgetreten.</strong><br><br>
                    Bitte versuchen Sie zu einem sp&auml;teren Zeitpunkt erneut, die Seite aufzurufen.<br><br>
                    Aktualisieren Sie die Plattform durch Betätigen der Tastenkombination [STRG]+[F5] bzw. [CTRL]+[F5].<br><br>
                    Sollte der Fehler weiterhin bestehen, kontaktieren Sie bitte unseren Support unter folgender Telefonnummer:<br><br>
                    <strong>' . $Brand->getErrorpagePhonenumber() . '</strong>
                </p>
                <p class="errorDescription" style="border-top: 1px solid #fff;">
                    <strong>An unexpected error occured.</strong><br><br>
                    Please retry later to open the page.<br><br>
                    Update the platform by pressing the key combination [CTRL]+[F5] or [CTRL]+[F5].<br><br>
                    If the error persists, please contact our support at the following telephone number:<br><br>
                    <strong>' . $Brand->getErrorpagePhonenumber() . '</strong>
                </p>
                ';
                echo $errorCustomerText;

            } /* elseif (
                $this->lastHandledException
                &&
                $this->lastHandledException instanceof DispatchException
            ) {

                $_GET['errorcode'] = '404';
                // start the output buffer
                \ob_start();
                \ob_implicit_flush(0);
                require_once DIR_APP . '/html/error.php';
                // get the contents of the output buffer
                $content = \ob_get_clean();

                echo $content;

            } */ elseif (
                $this->lastHandledException
                &&
                $this->lastHandledException instanceof Error
            ) {

                header('Location: error.php');

            } elseif (
                $this->lastHandledException
                &&
                $this->lastHandledException instanceof Exception
            ) {

                $_GET['errorcode'] = '';
                // start the output buffer
                \ob_start();
                \ob_implicit_flush(0);
                /** @noinspection PhpIncludeInspection */
                require_once APP_DIR . '/public/error.php';
                // get the contents of the output buffer
                $content = \ob_get_clean();

                echo $content;

            } else {

                header('Location: error.php');

            }

            // @codingStandardsIgnoreStart
            exit(1);
            // @codingStandardsIgnoreEnd
        }

        // called with "@", so that we can skip the error reporting
        if (error_reporting() === 0) {
            return;
        }

        if ($errno & (\E_ALL ^ (\E_NOTICE | \E_USER_NOTICE | \E_DEPRECATED | \E_USER_DEPRECATED | \E_STRICT))) {

            // - wenn der fehler keine E_NOTICE oder E_DEPRECATED oder die anderen sind
            // - E_USER_WARNING auch live verarbeiten

            if (IS_DEBUGMODUS || ($errno & \E_USER_WARNING)) {
                $warningStr = 'WarningNo:' . $errno . ' Error:' . $errstr . " File:" . $errfile . " Line:" . $errline . "\n\n";

                $str = '';
                $str .= $warningStr;
                //$str .= getLoginInfos();
                $str .= $warningStr;

                if ($backtrace === null) {
                    // @codingStandardsIgnoreStart
                    $backtrace = debug_backtrace();
                    // @codingStandardsIgnoreEnd
                }
                $str .= $this->backtrace($backtrace);

                if (!empty($context)) {
                    $str .= "\n" . print_r($context, true);
                }

                /* @noinspection ForgottenDebugOutputInspection */
                error_log($str);

                if (
                    IS_DEBUGMODUS
                    &&
                    $warningCounter <= 1
                ) {
                    $warningCounter++;

                    /** @noinspection MissingOrEmptyGroupStatementInspection */
                    /** @noinspection PhpStatementHasEmptyBodyInspection */
                    if (is_ajax_request()) {
                        // is already logged
                    } else {
                        echo '<pre style="margin-top: 25px;">';
                        echo 'Warning:' . $str;
                        echo '</pre>';
                    }
                }
            }

        } elseif ($errno != \E_STRICT && IS_DEBUGMODUS) {

            // - wenn der fehler keine E_STRICT ist

            $noticeStr = 'NoticeNo:' . $errno . ' Error:' . $errstr . " File:" . $errfile . " Line:" . $errline . "\n\n";

            $str = '';
            $str .= $noticeStr;
            //$str .= getLoginInfos();
            $str .= $noticeStr;

            if ($backtrace === null) {
                // @codingStandardsIgnoreStart
                $backtrace = debug_backtrace();
                // @codingStandardsIgnoreEnd
            }
            $str .= $this->backtrace($backtrace);

            if (!empty($context)) {
                $str .= "\n" . print_r($context, true);
            }

            /* @noinspection ForgottenDebugOutputInspection */
            error_log($str);

            if (
                IS_DEBUGMODUS
                &&
                $noticeCounter <= 1
            ) {
                $noticeCounter++;

                /** @noinspection MissingOrEmptyGroupStatementInspection */
                /** @noinspection PhpStatementHasEmptyBodyInspection */
                if (is_ajax_request()) {
                    // all notices are already logged, so we do nothing here
                } else {
                    echo '<pre style="margin-top: 25px;">';
                    echo 'Notice:' . $str;
                    echo '</pre>';
                }
            }

        }

        if (
            IS_DEVELOPMENT >= 1
            &&
            (
                (IS_DEBUGMODUS && !is_ajax_request())
                ||
                (IS_DEBUGMODUS && is_cli())
            )
        ) {
            // Switch back to PHP internal error handler.
            return false;
        }
    }

    /**
     * @return void
     */
    public function handleFatalError() {
        // free some memory ...
        self::$reservedMemory = null;
        // ... and try to increase the memory-limit
        ini_set('memory_limit', ((int)ini_get('memory_limit') + 5) . 'M');

        $error = error_get_last();
        if ($error === null) {
            return;
        }

        if ($this->shouldCaptureFatalError($error['type'], $error['message'])) {
            /* @noinspection PhpUsageOfSilenceOperatorInspection */
            $e = new ErrorException(
                @$error['message'],
                0,
                @$error['type'],
                @$error['file'],
                @$error['line']
            );

            $this->handleException($e);
        }
    }

    /**
     * @param int         $type
     * @param null|string $message
     *
     * @return bool
     */
    private function shouldCaptureFatalError($type, $message = null): bool {
        if ($this->lastHandledException) {
            if (
                $type === \E_CORE_ERROR
                &&
                strpos($message, 'Exception thrown without a stack frame') === 0
            ) {
                return false;
            }

            if (
                $type === \E_ERROR
                &&
                strpos($message, 'Uncaught ' . \get_class($this->lastHandledException) . ': ' . $this->lastHandledException->getMessage()) === 0
            ) {
                return false;
            }

        }

        /* @noinspection SuspiciousBinaryOperationInspection */
        return (bool)($type & $this->fatal_error_types);
    }

    /**
     * @param null|array<mixed> $backtrace      <p>backtrace from "debug_backtrace()"</p>
     * @param bool              $show_args      <p>Show arguments passed to functions? Default False.</p>
     * @param bool              $return         <p>Return result instead of printing it? Default False.</p>
     * @param bool              $show_args_full <p>Show all arguments passed to functions? Default False.</p>
     * @param bool              $for_web        <p>Show as html output or not e.g. for CLI. Default False.</p>
     *
     * @return string
     */
    private function backtrace(
        $backtrace = null,
        bool $show_args = true,
        bool $return = true,
        bool $show_args_full = false,
        bool $for_web = false
    ): string {

        if ($for_web) {
            /** @noinspection HtmlPresentationalElement */
            $before = '<b>';
            $after = '</b>';
            $tab = '&nbsp;&nbsp;&nbsp;&nbsp;';
            $newline = '<br>';
            $newlineAndTab = $newline . $tab;
        } else {
            $before = '<';
            $after = '>';
            $tab = "\t";
            $newline = "\n";
            $newlineAndTab = $newline . $tab;
        }

        // init
        $output = '';

        if (!$backtrace) {
            // @codingStandardsIgnoreStart
            $backtrace = \debug_backtrace();
            // @codingStandardsIgnoreEnd
        }

        if ($show_args && $show_args_full) {
            $output .= "\n\n" . 'Backtrace + Full-Parameter:' . "\n\n";
        } elseif ($show_args) {
            $output .= "\n\n" . 'Backtrace + Parameter:' . "\n\n";
        } else {
            $output .= "\n\n" . 'Backtrace:' . "\n\n";
        }

        $length = \count($backtrace);

        /* @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $length; $i++) {

            // reset
            $caller = '';

            if (isset($backtrace[$i]['class'])) {
                /** @noinspection PhpUsageOfSilenceOperatorInspection */
                $caller .= @$backtrace[$i]['class'];
            }

            if (isset($backtrace[$i]['type'])) {
                /* @noinspection PhpUsageOfSilenceOperatorInspection */
                $caller .= @$backtrace[$i]['type'];
            }

            /* @noinspection PhpUsageOfSilenceOperatorInspection */
            $caller .= @$backtrace[$i]['function'];

            $line = $before . ($backtrace[$i]['file'] ?? '') . $after . '"' . $caller . '" on line: ' . $before . ($backtrace[$i]['line'] ?? '') . $after . $newline;
            if ($i < $length - 1) {
                if (
                    $show_args
                    &&
                    !empty($backtrace[$i]['args'])
                ) {
                    // reset
                    $args = '';

                    if ($show_args_full) {

                        if ($for_web) {
                            $args = \htmlentities(\print_r($backtrace[$i]['args'], true), \ENT_QUOTES);
                        } else {
                            $args = \print_r($backtrace[$i]['args'], true);
                        }

                    } else {

                        /* @noinspection NestedPositiveIfStatementsInspection */
                        if (is_array($backtrace[$i]['args'])) {
                            foreach ($backtrace[$i]['args'] as $a) {

                                if (!empty($args)) {
                                    $args .= ', ';
                                }

                                $args .= self::getTypeInfo($a, $for_web);
                            }
                        }

                    }

                    $line .= $tab . 'Called with params: ' . \preg_replace('/(\n)/', $newline . $tab, trim($args)) . $newline . $newlineAndTab . 'By: ';
                    $args = null;
                } else {
                    $line .= $newlineAndTab . 'Called By: ';
                }
            }

            if ($return) {
                $output .= $line;
            } else {
                echo $line;
            }
        }

        if ($return) {
            return $output;
        }

        // if (IS_DEBUGMODUS) {
        //     $GLOBALS['timerPagesetup']->addToSlot('Backtrace - Done');
        // }

        return '';
    }

    /**
     * @param mixed $var
     * @param bool  $for_web
     *
     * @return string
     */
    public static function getTypeInfo($var, $for_web = true): string {
        // init
        $str = '';

        switch (\gettype($var)) { // PhpStan::foooobarrrSwitchMustContainDefinesRule -> need to use a string here?!
            case 'integer':
            case 'double':
                $str .= $var;

                break;
            case 'string':
                if ($for_web) {
                    $var = \htmlspecialchars(\substr($var, 0, 256), \ENT_QUOTES) . ((\strlen($var) > 256) ? '...' : '');
                } else {
                    $var = \print_r(\substr($var, 0, 256), true) . ((\strlen($var) > 256) ? '...' : '');
                }
                $str .= "\"${var}\"";

                break;
            case 'array':
                $varCount = \count($var);
                $str .= 'Array(' . $varCount . ')';
                if ($varCount > 0) {
                    $str .= ' {';
                    $varCountMax = 5;
                    $varCountTmp = 1;
                    /* @noinspection ForeachSourceInspection | already checked via "gettype()" */
                    foreach ($var as $varKey => $varInner) {

                        if (is_string($varKey)) {
                            $varKey = "'" . $varKey . "'";
                        }

                        $str .= $varKey . ' => ' . self::getTypeInfo($varInner, $for_web);

                        $varCountTmp++;

                        if ($varCountTmp > 1) {
                            $str .= ' | ';
                        }

                        if ($varCountTmp > $varCountMax) {
                            $str .= ' ... ';

                            break;
                        }
                    }
                    $str .= ' }';
                }

                break;
            case 'object':

                if ($var instanceof \Closure) {
                    $closureReflection = new \ReflectionFunction($var);
                    $str .= sprintf(
                        'Closure at %s:%s',
                        $closureReflection->getFileName(),
                        $closureReflection->getStartLine()
                    );
                } else {
                    $str .= 'Object(' . \get_class($var) . ')'; // .
                         //   ($var instanceof ActiveRow ? 'ActiveRow-ID: ' . $var->getId() : '') .
                         //   ($var instanceof Swift_Address ? 'Swift_Address: ' . $var->getAddress() . ' (' . $var->getName() . ')' : '');
                }

                break;
            case 'resource':
                $str .= 'Resource(' . \get_resource_type($var) . ')';

                break;
            case 'boolean':
                $str .= $var ? 'True' : 'False';

                break;
            case 'NULL':
                $str .= 'Null';

                break;
            default:
                $str .= 'Unknown';
        }

        return $str;
    }

    /**
     * @return $this
     */
    public function register(): self {

        $this->registerShutdownFunction()
             ->registerErrorHandler()
             ->registerExceptionHandler();

        if (IS_DEBUGMODUS) {
            return $this;
        }

        if (is_gitlab_ci()) {
            $errorTypes = [
                \E_ERROR,
                \E_WARNING,
                \E_NOTICE,
                \E_PARSE,
                \E_CORE_ERROR,
                \E_CORE_WARNING,
                \E_COMPILE_ERROR,
                \E_COMPILE_WARNING,
                \E_USER_ERROR,
                \E_USER_WARNING, // we use it for application warnings
                \E_STRICT,
                \E_RECOVERABLE_ERROR,
            ];
        } elseif (is_cli()) {
            $errorTypes = [
                \E_ERROR,
                \E_WARNING,
                // \E_NOTICE,
                \E_PARSE,
                \E_CORE_ERROR,
                \E_CORE_WARNING,
                \E_COMPILE_ERROR,
                \E_COMPILE_WARNING,
                \E_USER_ERROR,
                \E_USER_WARNING, // we use it for application warnings
                // \E_STRICT,
                \E_RECOVERABLE_ERROR,
            ];
        } else {
            $errorTypes = [
                \E_ERROR,
                \E_WARNING,
                // \E_NOTICE,
                \E_PARSE,
                \E_CORE_ERROR,
                \E_CORE_WARNING,
                \E_COMPILE_ERROR,
                \E_COMPILE_WARNING,
                \E_USER_ERROR,
                \E_USER_WARNING, // we use it for application warnings
                \E_STRICT,
                \E_RECOVERABLE_ERROR,
            ];
        }

        /*
        $errorTypes = array_reduce(
            $errorTypes,
            [$this, 'bitwiseOr']
        );
        */

        return $this;
    }

    /**
     * @param string $error_msg
     *
     * @return void
     */
    public static function foooobarrrReportError($error_msg) {
        trigger_error($error_msg, \E_USER_WARNING);
    }

    /**
     * @return $this
     */
    private function registerExceptionHandler(): self {
        $this->old_exception_handler = set_exception_handler([$this, 'handleException']);

        return $this;
    }

    /**
     * @return $this
     */
    private function registerErrorHandler(): self {
        $this->old_error_handler = set_error_handler([$this, 'handleError'], \E_ALL);

        return $this;
    }

    /**
     * Register a fatal error handler, which will attempt to capture errors which
     * shutdown the PHP process. These are commonly things like OOM or timeouts.
     *
     * @param int $reservedMemorySize <p>Number of MB (memory space) to reserve,
     *                                which is utilized when handling fatal errors.</p>
     *
     * @return $this
     */
    private function registerShutdownFunction($reservedMemorySize = 10): self {
        register_shutdown_function([$this, 'handleFatalError']);

        // fill some memory, so that we can free the memory on fatal errors ...
        self::$reservedMemory = str_repeat('x', 1024 * $reservedMemorySize);

        return $this;
    }

    /**
     * Gibt den globalen "window.onerror"-handler für JavaScript zurück.
     *
     * @return string
     */
    public static function getGlobalJavaScriptErrorHandler(): string {
        //global $loggedInUser;
        //global $Brand;

        // init
        $str = '';

        /*
        $systemVersion = SystemVersionFactory::singleton()->fetchByLastUpdate();
        if ($systemVersion) {
            $gitHash = $systemVersion->new_git_commit;
        } else {
            $gitHash = '';
        }

        // add sentry stuff
        $str .= '
        <script type="text/javascript" src="js/thirdparty/raven-3.27.2.min.js" crossorigin="anonymous"></script>
        ';

        if (!IS_DEBUGMODUS) {
            $str .= '
            <script type="text/javascript">
                if (typeof Raven === "object") {
                    Raven.config(\'FOOBAR\', {
                        environment: \'FOOBAR\',
                        release: \'123456\'
                    }).install();

                    Raven.setExtraContext({
                        location_href: window.location.href
                    });
                    Raven.setUserContext(' . json_encode(self::getSentryUserInfo()) . ');
                    Raven.setTagsContext(' . json_encode(self::getSentryTagInfo()) . ');
                }
            </script>
            ';
        }
        */

        // @see https://blog.sentry.io/2016/01/04/client-javascript-reporting-window-onerror.html
        /* @noinspection NestedConditionalExpressionJS */
        /* @noinspection JSUnresolvedVariable */
        /* @noinspection UnnecessaryReturnStatementJS */
        $str .= '
        <script type="text/javascript">
        
            /**
             * @param {String} message
             * @param {String} category
             * @param data
             * @param {String} level <p>fatal, error, warning, info, or debug</p>
            */
            var foooobarrrAddAdditionalInfo = function(message, category, data, level) {
                if (!level) {
                    level = \'info\';
                }
                
                if (!category) {
                    category = \'general_foooobarrr_info\';
                }
                
                if (!data) {
                    data = \'\';
                }
                
                var all_data = {
                    message:  message,
                    category: category,
                    data:     { extra: data },
                    level:    level
                };
                
                if (typeof Raven === "object") {
                    Raven.captureBreadcrumb(all_data);
                } else {
                    console.debug(all_data);
                }
            };
        
            var one_error_reported_via_onerror = false;

            /**
             * @param {String} msg
             * @param {String} url
             * @param {Int}    line
             * @param {Int}    col
             * @param error e.g. the previous Exception, so that we can captcha the error and continue the script execution.
             */
            var foooobarrrReportError = function(msg, url, line, col, error) {
            
                // fallback for e.g.: IE
                if (
                    !error 
                    && 
                    typeof Error === "function"
                ) {
                    error = new Error();
                }
                
                ' . (IS_DEBUGMODUS ? 'console.error(msg, url, line, col, error);' : '') . '
                
                if (typeof Raven === "object") {
                    Raven.captureException(error);
                }
                
                if (one_error_reported_via_onerror === true) {
                    // If you return true, then error alerts (like in older versions of 
                    // Internet Explorer) will be suppressed.
                    return ' . (IS_DEBUGMODUS ? 'false' : 'true') . ';
                }
                
                var string_tmp = "";
                try {
                    string_tmp = msg.toLowerCase();
                } catch (exception) {
                    // ignore the error if msg was non string
                    string_tmp = "";
                }
                var substring_tmp = "script error";
                  
                if (string_tmp.indexOf(substring_tmp) > -1){
                
                    // https://developer.mozilla.org/en/docs/Web/API/GlobalEventHandlers/onerror
                    //
                    //alert(\'Script Error: See Browser Console for Detail\');
                
                } else {

                    one_error_reported_via_onerror = true;

                    var message_msg = msg ? msg : "";
                    var message_url = url ? url : "";
                    var message_line = line ? line : "";
                    var message_col = col ? col : (window.event && window.event.errorCharacter ? window.event.errorCharacter : "");
                    var message_userAgent = navigator && navigator.userAgent ? navigator.userAgent : \'\';

                    // get information about the error e.g. "stack trace" ... (only for new browsers)
                    var message_error = "";
                    if (error) {
                        message_error = error;
                        if (JSON && typeof JSON.stringify === "function") {
                            message_error = JSON.stringify(error);
                        }
                    }
                    
                    // Something like a "stack trace" for old browsers ...
                    var error_stack_string = "no stack info";
                    if (typeof error.stack !== "undefined") { 
                    
                        // e.g.: Opera 11, FF, ...
                        error_stack_string = error.stack;
                    
                    } else if (typeof error.stacktrace !== "undefined") { 
                    
                        // e.g.: Opera 10
                        error_stack_string = error.stacktrace;
                    
                    } else if (typeof error.trace === "function") { 
                    
                        // e.g.: IE 11
                        error_stack_string = error.trace();
                    
                    } else if (typeof error.message !== "undefined") { 
                    
                        // e.g.: Opera 9
                        error_stack_string = error.message;
                    
                    } else if (
                        typeof arguments === "object" 
                        && 
                        typeof arguments.callee.caller === "function"
                    ) { 
                    
                        // e.g. IE <= 8 (using arguments.callee.caller is deprecated - not working in strict mode in any browser!)
                        error_stack_string = arguments.callee.caller.toString();
                    
                    }
                    
                    // Report this error via ajax so you can keep track of what pages have JS issues.
                    jQuery.ajax({
                        type: "POST",
                        url: "error_js.php",
                        data: { 
                            msg: message_msg,
                            url: message_url,
                            line: message_line,
                            col: message_col,
                            error: message_error,
                            stack: error_stack_string,
                            browser: message_userAgent,
                            code: $("html").html(),
                            location_href: window.location.href
                        },
                        success: function() {
                            console.log("JS error report successful.");
                        },
                        error: function() {
                            console.error("JS error report submission failed!");
                        }
                    });
                
                }

                // If you return true, then error alerts (like in older versions of 
                // Internet Explorer) will be suppressed.
                return ' . (IS_DEBUGMODUS ? 'false' : 'true') . ';
            };
                
            window.onerror = foooobarrrReportError;
        </script>
        ';

        return $str;
    }
}
