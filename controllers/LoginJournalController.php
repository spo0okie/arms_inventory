<?php

namespace app\controllers;

use app\models\LoginJournal;

/**
 * OrgPhonesController implements the CRUD actions for OrgPhones model.
 */
class LoginJournalController extends ArmsBaseController
{
	public function disabledActions()
	{
		//все CRUD операции делаются через REST API
		return ['item-by-name','item','create','update','delete','view'];
	}
	
	public $modelClass=LoginJournal::class;
}
