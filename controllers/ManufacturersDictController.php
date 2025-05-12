<?php

namespace app\controllers;

use app\models\ManufacturersDict;

/**
 * ManufacturersDictController implements the CRUD actions for ManufacturersDict model.
 */
class ManufacturersDictController extends ArmsBaseController
{
	public function disabledActions()
	{
		return ['item-by-name','ttip'];
	}
	
	public $modelClass=ManufacturersDict::class;
}
