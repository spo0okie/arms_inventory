<?php

namespace app\modules\api\controllers;


class PartnersController extends BaseRestController
{
    public $modelClass='app\models\Partners';
    public static $searchFields=[
    	'bname'=>'bname',
		'uname'=>'uname',
		'name'=>BaseRestController::SEARCH_BY_ANY_NAME,
	];
}
