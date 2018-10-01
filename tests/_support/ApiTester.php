<?php


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
class ApiTester extends \Codeception\Actor {

    use _generated\ApiTesterActions;

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

        $tmpTester = new AcceptanceTester($scenario);
        $this->httpWebServer = $tmpTester->httpWebServer;
    }
}
