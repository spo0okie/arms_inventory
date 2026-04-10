<?php

namespace app\controllers;

use app\models\Manufacturers;

/**
 * ManufacturersController implements the CRUD actions for Manufacturers model.
 */
class ManufacturersController extends ArmsBaseController
{
	public function testItemByName(): array
	{
		return self::skipScenario('default', 'name lookup depends on dictionary normalization');
	}
	
	public $modelClass=Manufacturers::class;
}
