<?php

class SomethingExamplePageView_Lall_ApiCest {

    public function testViewTable(ApiTester $I) {
        $testUrl = $I->httpWebServer . '/index.php?api=1&view=' . SomethingExamplePageView::getUrl();

        $I->wantTo('Test if we can see the example page of the something module via API.');
        $I->sendGET($testUrl);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseCodeIsSuccessful();
        $I->canSeeResponseContains('Page Title');
    }
}
