<?php

class SomethingExamplePageView_Lall_AcceptanceCest extends MainAcceptanceCest {

    /**
     * @param AcceptanceTester $I
     */
    public function testView(AcceptanceTester $I) {
        $I->wantTo('Test if we can see the example page of the something module.');
        $I->amOnUrlWithHtmlValidate($I, $I->httpWebServer . '/index.php?view=' . SomethingExamplePageView::getUrl());
        $I->see('My First Heading', 'body');
    }
}
