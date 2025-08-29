<?php

class CompsCest
{
    public function _before(ApiTester $I)
    {
    }

    // tests
	public function index(ApiTester $I)
	{
		$I->sendGET('/comps');
		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJson();
		$I->canSeeResponseJsonMatchesJsonPath('[*].name');
	}

	public function searchByName(ApiTester $I)
	{
		$I->sendGET('/comps/search', ['name' => 'msk-esxi1']);
		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson(['name' => 'MSK-ESXI1']);
	}
}
