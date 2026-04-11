<?php

namespace app\controllers;

use app\models\AccessTypes;
use yii\web\Response;
use Yii;


/**
 * AccessTypesController implements the CRUD actions for AccessTypes model.
 */
class AccessTypesController extends ArmsBaseController
{
	public $modelClass = AccessTypes::class;
	

	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'edit'=>['access-types-form']
		]);
	}
	
	/**
	 * Возвращает JSON-настройки выбранных типов доступа (включая дочерние типы).
	 *
	 * Для корректного вызова необходимо передать GET-параметр `access_types_ids` как массив ID.
	 *
	 * @param array $access_types_ids Список ID типов доступа.
	 * @return array
	 */
    public function actionAccessTypesForm(array $access_types_ids) {
		Yii::$app->response->format=Response::FORMAT_JSON;
		return AccessTypes::bundleAccessTypes($access_types_ids);
    }

	/**
	 * Тестовые данные для actionAccessTypesForm.
	 *
	 * Для теста нужно передать массив `access_types_ids` с существующими ID типов доступа.
	 * Сейчас тест пропущен, потому что в изолированном acceptance-прогоне нет гарантии
	 * стабильного набора связанных ACL/ACE/AccessTypes для воспроизводимого результата.
	 * Чтобы тестировать метод, нужно подготовить фиксированные фикстуры типов доступа
	 * и связей между ними (включая дочерние типы).
	 */
	public function testAccessTypesForm(): array
	{
		$empty=	$this->getTestData()['empty'];
		$full=	$this->getTestData()['full'];
		$upd=	$this->getTestData()['update'];
		return [
			[
				'name' => 'default form data',	 
				'GET' => [
					'access_types_ids' => [
						$full->id,
						$empty->id,
						$upd->id,
					]
				],	
				'response' => 200,
			],
		];
	}
}
