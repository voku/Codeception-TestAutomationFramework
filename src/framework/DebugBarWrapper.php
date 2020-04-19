<?php

use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DebugBar;
use DebugBar\JavascriptRenderer;

/**
 * DebugBarWrapper: Show debug-info for admin / dev.
 */
final class DebugBarWrapper {

    /**
     * @var DebugBarWrapper[]
     */
    private static $instances = [];

    /**
     * @var null|DebugBar
     */
    private $debugBar;

    /**
     * __construct
     */
    private function __construct() {
        if (self::isDebugRequest()) {
            $this->debugBar = new DebugBar();

            $this->debugBar->addCollector(new MessagesCollector());

            $this->debugBar->addCollector(new MessagesCollector('php_error'));

            $this->debugBar->addCollector(new MessagesCollector('tooltip'));

            //$this->debugBar->addCollector(new DebugBarTimeDataCollectorVdmg());
        }
    }

    /**
     * @return void
     */
    public function __clone() {
    }

    /**
     * @return void
     */
    public function __wakeup(): void {
    }

    /**
     * get the current debug-bar
     *
     * @return null|DebugBar
     */
    public function getDebugBar(): ?DebugBar {
        return $this->debugBar;
    }

    /**
     * @return void
     */
    private function collectTimeingInfo(): void {
        $debugBar = $this->getDebugBar();
        if ($debugBar instanceof DebugBar && isset($GLOBALS['timerPagesetup'])) {
            foreach ((array)$GLOBALS['timerPagesetup']->slot_array_helper as $slotArray) {
                $debugBar['time']->appendMeasure(
                    $slotArray[0],
                    $slotArray[1],
                    $slotArray[2],
                    $slotArray[3] ?? ''
                );
            }
        }
    }

    /**
     * @return void
     */
    private function collectData(): void {
        global $MainDB;
        global $RemoteDB;
        global $ShardedDB;

        // $this->debugBar->addCollector(new DebugBarCollectorRequest());

        /*
        $this->debugBar->addCollector(
            new DebugBarCollectorLogger(
                null,
                $MainDB->queryLog .= '[' . \date('Y-m-d H:i:s') . ']: INFO -> MainDB -> queryCnt:' . $MainDB->queryCnt . ' | microtimeUsedBySql:' . $GLOBALS['microtimeUsedBySql'] . "\n"
                                     . ($RemoteDB ? $RemoteDB->queryLog .= '[' . \date('Y-m-d H:i:s') . ']: INFO -> RemoteDB -> queryCnt:' . $RemoteDB->queryCnt . ' | microtimeUsedBySql:' . $GLOBALS['microtimeUsedBySql'] . "\n" : '')
                                     . ($ShardedDB ? $ShardedDB->queryLog .= '[' . \date('Y-m-d H:i:s') . ']: INFO -> ShardedDB -> queryCnt:' . $ShardedDB->queryCnt . ' | microtimeUsedBySql:' . $GLOBALS['microtimeUsedBySql'] . "\n" : ''),
                'SQL-Log',
                'database'
            )
        );
        */

        //$this->debugBar->addCollector(new DebugBarCollectorLinks());

        //$this->debugBar->addCollector(new DebugBarCollectorBrand());

        //$this->debugBar->addCollector(new DebugBarCollectorKunde());

        $this->debugBar->addCollector(new MemoryCollector());

        //$this->debugBar->addCollector(new DebugBarCollectorVdmg());
    }

    /**
     * Returns the code needed to display the debug bar
     *
     * AJAX request should not render the initialization code.
     *
     * @param bool $initialize        Whether or not to render the debug bar initialization code
     * @param bool $renderStackedData Whether or not to render the stacked data
     *
     * @return string
     */
    public function render($initialize = true, $renderStackedData = true): string {
        global $timerPagesetup;

        $this->collectData();

        if (IS_DEBUGMODUS) {
            $timerPagesetup->addToSlot('Display DebugBar - collect data');
        }

        $this->collectTimeingInfo();

        return $this->getDebugBarRenderer()->render($initialize, $renderStackedData);
    }

    /**
     * Renders the html to include needed assets
     *
     * Only useful if Assetic is not used
     *
     * @return string
     */
    public function renderHead(): string {
        return $this->getDebugBarRenderer()->renderHead();
    }

    /**
     * get the current debug-bar-renderer
     *
     * @return JavascriptRenderer
     */
    private function getDebugBarRenderer(): JavascriptRenderer {
        $tmpPath = 'frontend/debugbar/';
        $debugBarRenderer = $this->debugBar->getJavascriptRenderer(
            $tmpPath,
            APP_DIR . '/' . $tmpPath
        );

        return $debugBarRenderer;
    }

    /**
     * Singleton Instance
     *
     * Returns a Singleton instance of the parent class.
     *
     * @return self
     */
    public static function getInstance(): self {
        $class = __CLASS__;

        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }

        return self::$instances[$class];
    }

    /**
     * check if this is a debug-request
     *
     * @return bool
     */
    public static function isDebugRequest(): bool {
        global $loggedInUser;

        return ($loggedInUser && is_gitlab_ci())
               ||
               IS_DEBUGMODUS === true;
    }

    /**
     * this is only a hack for unit-tests + singleton :/
     *
     * @return void
     */
    public static function tearDown(): void {
        self::$instances = [];
    }
}
