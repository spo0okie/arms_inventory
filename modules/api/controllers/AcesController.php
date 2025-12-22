<?php

namespace app\modules\api\controllers;




use app\models\Aces;

class AcesController extends BaseRestController
{
    
    public $modelClass=Aces::class;
	
	public static array $searchFields=[
		'id',
		'accessTypes',
		'accessTypeName'=>'accessTypes',
	];
	
}
