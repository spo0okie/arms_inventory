<?php

namespace app\controllers;

use app\models\ui\UiTablesCols;
use Yii;
use yii\web\NotFoundHttpException;
use yii\helpers\Url;


/**
 * UiTablesColsController implements the CRUD actions for UiTablesCols model.
 */
class UiTablesColsController extends ArmsBaseController
{

	public function accessMap()
	{
		return [ArmsBaseController::PERM_AUTHENTICATED=>['set','get','delete']];
	}
	
	
	/**
	 * @param string $table
	 * @param string $column
	 * @param int    $user
	 * @param string $value
	 * @return mixed
	 */
    public function actionSet(string $table, string $column, int $user, string $value)
    {
        $model = UiTablesCols::find()->where([
			'table'=>$table,
			'column'=>$column,
			'user_id'=>$user,
		])->one();
        
        if (!is_object($model)) {
        	$model=new UiTablesCols([
				'table'=>$table,
				'column'=>$column,
				'user_id'=>$user,
			]);
		}
        
        $model->value = $value;
		
        $model->save();
			
    }

}
