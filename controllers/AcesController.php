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
		return (is_object($model->acl) && $model->acl->schedules_id)?
			['/scheduled-access/view','id'=>$model->acl->schedules_id]:
			['/scheduled-access/index'];
    }
}
