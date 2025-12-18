<?php

use app\helpers\ArrayHelper;
use app\helpers\StringHelper;
use Codeception\TestInterface;
use yii\base\InvalidConfigException;

class ExtraPageAccessCest
{
	public $savedModels=[];
	public $rootDb;
	
	
	public function _failed($test, $fail)
	{
		Helper\Acceptance::$testsFailed = true;
	}
	
	/**
	 * Проверяем что у нас нормально рендерится корзина/шкаф
	 * @param AcceptanceExtraTester $I
	 * @return void
	 */
	public function testTechsRackRender(AcceptanceExtraTester $I)
	{
		$I->amOnPage('/techs/view?id=18');
		$I->seeResponseCodeIs(200,"Techs rack render OK");
	}
	
	public function testTechsServerRender(AcceptanceExtraTester $I)
	{
		$I->amOnPage('/techs/view?id=1');
		$I->seeResponseCodeIs(200,"Techs server render OK");
	}
	
	public function testCompsRender(AcceptanceExtraTester $I)
	{
		$I->amOnPage('/techs/view?id=3');
		$I->seeResponseCodeIs(200,"Windows server render OK");
	}
}
