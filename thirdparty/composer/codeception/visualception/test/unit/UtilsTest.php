<?php

namespace Codeception\Module\VisualCeption\Test\Unit;

use PHPUnit\Framework\TestCase;
use Codeception\Module\VisualCeption\Utils;

class UtilsTest extends TestCase
{
    public function testGetTestFileName()
    {
        $utils = new Utils();
        $this->assertEquals('Acceptance.Work.Test.test.screenshot.png', $utils->getTestFileName('Acceptance\Work\Test:test', 'screenshot'));
        $this->assertEquals('Test.test.screenshot.png', $utils->getTestFileName('Test:test', 'screenshot'));
    }
}
