<?php

namespace app\controllers;

use app\models\LoginJournal;

/**
 * OrgPhonesController implements the CRUD actions for OrgPhones model.
 */
class LoginJournalController extends ArmsBaseController
{
	public function testUpdate(): array
	{
		return self::skipScenario('default', 'update is not allowed');
	}
	
	public function testValidate(): array
	{
		return self::skipScenario('default', 'validate is not applicable');
	}
	public function disabledActions()
	{
		//все CRUD операции делаются через REST API
		return ['item-by-name','item','create','update','delete','view'];
	}
	
	public $modelClass=LoginJournal::class;
}
