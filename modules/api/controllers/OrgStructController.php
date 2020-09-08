<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 19.04.2020
 * Time: 15:41
 */

namespace app\modules\api\controllers;
use app\models\OrgStruct;


class OrgStructController extends \yii\rest\ActiveController
{
	public $modelClass='app\models\OrgStruct';
	
	public function actions()
	{
		$actions = parent::actions();
		unset($actions['index']);
		return $actions;
	}
}
