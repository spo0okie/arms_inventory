<?php

namespace app\modules\api\controllers;

use app\models\LicItems;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;

/**
 * Class LicKeysController
 * @package app\modules\api\controllers
 * @noinspection PhpUnusedElementInspection
 */
class LicItemsController extends BaseRestController
{

	public $modelClass='app\models\LicItems';
	
	/**
	 * Возвращает единственную закупку лицензии (LicItems), привязанную к компьютеру или пользователю
	 * по указанному продукту. Фильтрация выполняется через LicLinksController::filterQuery().
	 *
	 * GET-параметры:
	 * @param int|null    $product_id   ID программного продукта (Soft)
	 * @param string|null $comp_name    Имя компьютера (Comps)
	 * @param string|null $user_login   Логин пользователя (Users)
	 *
	 * @return ActiveRecord|null
	 */
	public function actionSearch($product_id=null,$comp_name=null,$user_login=null): ActiveRecord|null {
		return LicLinksController::filterQuery(
			LicItems::find(),
			'items',
			$product_id,
			$user_login,
			$comp_name
		)->one();
	}
	/**
	 * Возвращает список закупок лицензий (LicItems), привязанных к компьютеру или пользователю
	 * по указанному продукту. Результат оборачивается в ActiveDataProvider.
	 * Фильтрация выполняется через LicLinksController::filterQuery().
	 *
	 * GET-параметры:
	 * @param int|null    $product_id   ID программного продукта (Soft)
	 * @param string|null $comp_name    Имя компьютера (Comps)
	 * @param string|null $user_login   Логин пользователя (Users)
	 *
	 * @return ActiveDataProvider
	 */
	public function actionFilter($product_id=null,$comp_name=null,$user_login=null): ActiveDataProvider{
		return new ActiveDataProvider(['query' => LicLinksController::filterQuery(
			LicItems::find(),
			'items',
			$product_id,
			$user_login,
			$comp_name
		)]);
	}
}
