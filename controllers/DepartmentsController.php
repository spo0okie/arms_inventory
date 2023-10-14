<?php

namespace app\controllers;

use app\models\Departments;


/**
 * DepartmentsController implements the CRUD actions for Departments model.
 */
class DepartmentsController extends ArmsBaseController
{
	public $modelClass=Departments::class;
}
