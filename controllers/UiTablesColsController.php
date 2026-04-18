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
	 * Возвращает список отключенных действий базового CRUD.
	 *
	 * Контроллер работает только с endpoint `set`; inherited CRUD-методы
	 * для этой модели не используются и отключаются точечно.
	 *
	 * @return array<string>
	 */
	public function disabledActions(): array
	{
		return ['index', 'async-grid', 'item', 'item-by-name', 'ttip', 'view', 'validate', 'create', 'update', 'delete', 'editable'];
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

	/**
	 * Acceptance test data for Set.
	 *
	 * Покрывает:
	 * - создание новой настройки,
	 * - повторный вызов (обновление существующей),
	 * - граничный id пользователя (значение 0).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function testSet(): array
	{
		return [
			[
				'name' => 'create-setting',
				'GET' => [
					'table' => 'acceptance-grid',
					'column' => 'name',
					'user' => 1,
					'value' => '120',
				],
				'response' => 200,
			],
			[
				'name' => 'update-setting',
				'GET' => [
					'table' => 'acceptance-grid',
					'column' => 'name',
					'user' => 1,
					'value' => '240',
				],
				'response' => 200,
			],
			[
				'name' => 'invalid-user',
				'GET' => [
					'table' => 'acceptance-grid',
					'column' => 'name',
					'user' => 0,
					'value' => '300',
				],
				'response' => 200,
			],
		];
	}

}
