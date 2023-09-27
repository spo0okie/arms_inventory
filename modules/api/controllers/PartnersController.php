<?php

namespace app\modules\api\controllers;


class PartnersController extends BaseRestController
{
    public $modelClass='app\models\Partners';
    public static $searchFields=[
    	'name'=>'bname',
		'uname'=>'uname'
	];
}
