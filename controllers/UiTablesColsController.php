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

	/**
	* {@inheritdoc}
	*/
	public function behaviors()
	{
		$behaviors=[];
		if (!empty(Yii::$app->params['useRBAC'])) $behaviors['access']=[
			'class' => \yii\filters\AccessControl::className(),
			'rules' => [
				['allow' => true, 'actions'=>['set','get','delete',], 'roles'=>['@']],
			],
			'denyCallback' => function ($rule, $action) {
				throw new  \yii\web\ForbiddenHttpException('Access denied');
			}
		];
		return $behaviors;
	}


    /**
     * @return mixed
     */
    public function actionSet($table, $column, $user, $value)
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
