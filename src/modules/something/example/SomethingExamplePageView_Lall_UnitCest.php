<?php

class SomethingExamplePageView_Lall_UnitCest {

    public function testAdminBereichAusgeblendet(UnitTester $I) {
        $landingPage = new SomethingExamplePageView();
        $html = $landingPage->show();

        $I->wantTo('Test if we can see the example page of the something module.');
        $I->assertContains('My First Heading', $html);
    }
}
