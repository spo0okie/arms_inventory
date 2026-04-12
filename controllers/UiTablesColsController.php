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
	 * Returns disabled acceptance tests list.
	 */
	public function disabledTests(): array
	{
		return ['*'];
	}

	public function accessMap()
	{
		return [ArmsBaseController::PERM_AUTHENTICATED=>['set','get','delete']];
	}
	
	
	/**
	 * Сохраняет пользовательскую настройку видимости/ширины колонки таблицы.
	 * Ищет существующую запись по комбинации table+column+user_id; если не находит —
	 * создаёт новую. Устанавливает поле `value` и сохраняет запись.
	 *
	 * GET-параметры:
	 * @param string $table   Идентификатор таблицы (например, 'comps-index')
	 * @param string $column  Имя атрибута/колонки модели
	 * @param int    $user    ID пользователя (Users)
	 * @param string $value   Сохраняемое значение настройки (ширина, видимость и т.п.)
	 *
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
