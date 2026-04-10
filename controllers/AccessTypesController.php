<?php

namespace app\controllers;

use app\models\AccessTypes;


/**
 * AccessTypesController implements the CRUD actions for AccessTypes model.
 */
class AccessTypesController extends ArmsBaseController
{
	public $modelClass = AccessTypes::class;
	
	public function testItemByName(): array
	{
		return self::skipScenario('default', 'AccessTypes has no name field');
	}
	
	public function testTtip(): array
	{
		return self::skipScenario('default', 'AccessTypes uses id-based ttip');
	}
}
