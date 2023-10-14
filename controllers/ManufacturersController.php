<?php

namespace app\controllers;

use app\models\Manufacturers;

/**
 * ManufacturersController implements the CRUD actions for Manufacturers model.
 */
class ManufacturersController extends ArmsBaseController
{
	public $modelClass=Manufacturers::class;
}
