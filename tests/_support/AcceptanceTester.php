<?php

/** @noinspection PhpLanguageLevelInspection | used only for tests ... */

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor {

    use _generated\AcceptanceTesterActions;

    /**
     * Define custom actions here
     */

    /**
     * @var string
     */
    public $httpWebServer = '';

    /**
     * AcceptanceTester constructor.
     *
     * @param $scenario
     */
    public function __construct(\Codeception\Scenario $scenario) {
        parent::__construct($scenario);

        $env = $this->getCurrentEnvironment($scenario);

        if ($env == 'local' || $env == 'local-headless') {
            $this->httpWebServer = 'http://localhost:8887';
        } else {

            // remove "-non-headless"-helper-string, defined in "acceptance.suite.yml"
            $env = str_replace('-non-headless', '', $env);

            $this->httpWebServer = 'https://' . $env . '.your-testing-server';
        }
    }

    /**
     * @param AcceptanceTester $I
     * @param string           $fileUrl
     */
    public function amOnUrlWithHtmlValidate($I, $fileUrl) {
        static $CHECKED_URL_CACHE = array();

        // $startTime = \microtime(true);
        $I->amOnUrl($fileUrl);
        // $endTime = \microtime(true);

        // Check if we could navigate the the new page. We had problems with redirects,
        // so lets check if we are really on the correct url ...
        $I->cantSeeCurrentUrlEquals($fileUrl);

        $cacheKey = md5($fileUrl);
        if (array_key_exists($cacheKey, $CHECKED_URL_CACHE) === true) {
            // already checked, so skip the html-check
            return;
        }

        $I->comment('run html-validation for: ' . $fileUrl);

        // init
        $ignoreViewError = array();

        $ignoreGeneralError = array(
            '/CSS: Parse Error./', // chrome returns "&gt;" for ">" in <style>-tags :/
        );

        // do ignore warnings and ignore some errors
        try {
            $I->validateMarkup(
                array(
                    'ignoreWarnings' => true,
                    'ignoredErrors'  => array_merge($ignoreGeneralError, $ignoreViewError),
                )
            );
        } catch (\GuzzleHttp\Exception\ConnectException $e) { // GuzzleHttp from "codecept_php55.phar"
            $I->comment('connection error: ' . $e->getMessage());
        } catch (\GuzzleHttp\Exception\RequestException $e) { // GuzzleHttp from "codecept_php55.phar"
            $I->comment('request error: ' . $e->getMessage());
        } catch (\Exception $e) {
            if ($e->getMessage() == 'Unable to obtain current page markup.') {
                $I->comment('request error: ' . $e->getMessage());
            } else {
                // re-throw the exception
                throw $e;
            }
        }

        $CHECKED_URL_CACHE[$cacheKey] = 1;
    }

    /**
     * Define custom actions here
     *
     * @param \Codeception\Scenario $scenario
     */

    /**
     * @param \Codeception\Scenario $scenario
     *
     * @return string
     */
    public function getCurrentEnvironment(\Codeception\Scenario $scenario) {
        return $scenario->current('env');
    }

    /**
     * Change a checkbox to "checked".
     *
     * INFO: "$I->checkOption()" will toggle the checked state
     *
     * ``` php
     * <?php
     * $I->checkedOptionAlwaysChecked('#agree');
     * ?>
     * ```
     *
     * @param string $css
     */
    public function checkedOptionAlwaysChecked($css) {
        if ($this->grabAttributeFrom($css, 'checked') != 'checked') {
            $this->checkOption($css);
        }
        $this->canSeeCheckboxIsChecked($css);
    }

    public function seePageHasElement($element) {
        try {
            $this->seeElement($element);
        } catch (\PHPUnit\Framework\AssertionFailedError $f) {
            return false;
        }

        return true;
    }
}
