<?php

namespace app\modules\api\controllers;




use app\models\Aces;

class AcesController extends BaseRestController
{
    
    public $modelClass=Aces::class;
    
    /*public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			ArmsBaseController::PERM_AUTHENTICATED=>['whoami']
		]);
	}*/
	
	public static $searchJoin=[
		'accessTypes',
	];
	
	public static $searchFields=[
		'id',
		'accessTypeName'=>'access_types.name',
	];
	
}
