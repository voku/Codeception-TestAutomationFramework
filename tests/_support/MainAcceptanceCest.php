<?php

class MainAcceptanceCest {

    /**
     * @var string[]
     */
    public $cookie;

    /**
     * @var string
     */
    public $user_cid = 'user...';

    /**
     * @var string
     */
    public $user_pwd = 'password...';

    /**
     * @var bool
     */
    public $debug_php_session = false;

    /**
     * @param AcceptanceTester $I
     */
    public function _before(AcceptanceTester $I) {
        if ($this->debug_php_session === true) {
            echo "\n\n" . 'PHPSESSID (before v1): ' . $I->grabCookie('PHPSESSID') . "\n\n";
        }

        // $this->_login($I, $this->user_cid, $this->user_pwd);

        if ($this->debug_php_session === true) {
            echo "\n\n" . 'PHPSESSID (before v2): ' . $I->grabCookie('PHPSESSID') . "\n\n";
        }
    }

    /**
     * @param AcceptanceTester $I
     */
    public function _after(AcceptanceTester $I) {
        if ($this->debug_php_session === true) {
            echo "\n\n" . 'PHPSESSID (after): ' . $I->grabCookie('PHPSESSID') . "\n\n";
        }
    }

    /**
     * @param null|string $user_cid
     * @param null|string $user_pwd
     *
     * @return bool
     */
    protected function unsetLoginCookie($user_cid = null, $user_pwd = null) {
        if ($user_cid === null) {
            $user_cid = $this->user_cid;
        }

        if ($user_pwd === null) {
            $user_pwd = $this->user_pwd;
        }

        $loginHash = $this->generateLoginHash($user_cid, $user_pwd);

        if (isset($this->cookie[$loginHash])) {
            unset($this->cookie[$loginHash]);

            return true;
        }

        return false;
    }

    /**
     * @param null|string $user_cid
     * @param null|string $user_pwd
     *
     * @return string
     */
    private function generateLoginHash($user_cid = null, $user_pwd = null) {
        if ($user_cid === null) {
            $user_cid = $this->user_cid;
        }

        if ($user_pwd === null) {
            $user_pwd = $this->user_pwd;
        }

        $loginHash = md5($user_cid) . md5($user_pwd);

        return $loginHash;
    }

    /**
     * @param AcceptanceTester $I
     * @param string           $user_cid         anmeldename
     * @param string           $user_pwd         passwort
     * @param bool             $unsetLoginCookie
     */
    protected function _login(AcceptanceTester $I, $user_cid, $user_pwd, $unsetLoginCookie = false) {
        // problem via PhantomJS
        //
        // url: https://github.com/Codeception/Codeception/issues/2965#issuecomment-207347592
        /*
        $I->amOnUrl($I->httpWebServer . '/index.php?view=SystemLogin');
        $I->fillField('cid', $this->user_cid);
        $I->fillField('pwd', $this->user_pwd);
        $I->click('.loginsubmit');
         */

        if ($unsetLoginCookie === true) {
            $this->unsetLoginCookie($this->user_cid, $this->user_pwd);
        }

        $loginHash = $this->generateLoginHash($user_cid, $user_pwd);

        if (isset($this->cookie[$loginHash])) {

            //
            // see "acceptance.suite.yml" -> "clear_cookies"
            //
            $I->setCookie('PHPSESSID', $this->cookie[$loginHash]);

            $I->setCookie('TESTS_ARE_RUNNING', '1');
            //$I->setCookie('XDEBUG_SESSION', 'PHPSTORM');

        } else {

            $I->amOnUrlWithHtmlValidate($I,'login.php');
            $I->canSeeElement('.test-login-button');

            $I->fillField('cid', $user_cid);
            $I->fillField('pwd', $user_pwd);
            $I->click('.test-login-button');

            $I->waitForElement('.test-user-loggedin');

            $this->cookie[$loginHash] = $I->grabCookie('PHPSESSID');

            $I->setCookie('TESTS_ARE_RUNNING', '1');
            //$I->setCookie('XDEBUG_SESSION', 'PHPSTORM');
        }

        $this->user_cid = $user_cid;
        $this->user_pwd = $user_pwd;
    }

    protected function _logout(AcceptanceTester $I, $unsetLoginCookie = false) {

        $I->click('.test-link-logout');
        $I->amOnUrlWithHtmlValidate($I, '/login.php');

        if ($unsetLoginCookie) {
            $this->unsetLoginCookie();
        }
    }

    /**
     * @param AcceptanceTester $I
     */
    protected function _resetPhpSessionID(AcceptanceTester $I) {
        $I->resetCookie('PHPSESSID');
    }
}
