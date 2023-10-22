<?php

namespace app\controllers;

use app\models\Aces;


/**
 * AcesController implements the CRUD actions for Aces model.
 */
class AcesController extends ArmsBaseController
{
	public $modelClass=Aces::class;
	
	/**
	 * @inheritdoc
	 */
    public function routeOnDelete($model)
    {
    	/** @var Aces $model */
		$acl=$model->acl;
		$schedules_id=is_object($acl)?$acl->schedules_id:0;
		return ($schedules_id)?
			['/scheduled-access/view','id'=>$schedules_id]:
			['/scheduled-access/index'];
    }
}
