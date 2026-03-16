<?php

namespace app\modules\api\controllers;



class LicTypesController extends BaseRestController
{
	public static array $searchFields = [
		'product_id',
		'comp_name',
		'user_login',
	];
	
	public $modelClass='app\models\LicTypes';
}
