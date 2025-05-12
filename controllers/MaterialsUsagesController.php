<?php

namespace app\controllers;

use app\models\MaterialsUsages;

/**
 * MaterialsUsagesController implements the CRUD actions for MaterialsUsages model.
 */
class MaterialsUsagesController extends ArmsBaseController
{
	public function disabledActions()
	{
		return ['item-by-name',];
	}
	
	public $modelClass=MaterialsUsages::class;
}
