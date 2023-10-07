<?php

namespace app\modules\api\controllers;




class UsersController extends BaseRestController
{
    
    public $modelClass='app\models\Users';
	
	public static $searchFields=[
		'id',
		'Ename'=>'Ename',
		'name'=>'Ename',
		'employee_id'=>'employee_id',
		'num'=>'employee_id',
		'login'=>'Login',
		'org'=>'org_id',
		'org_id'=>'org_id',
		'uid'=>'uid'
	];
	
	public static $searchFieldsLike=[
		'mobile'=>'mobile'
	];
	
	public static $searchOrder=[
		'Uvolen'=>SORT_ASC,
		'Persg'=>SORT_ASC,
	];
	
}
