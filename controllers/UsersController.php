<?php

namespace app\controllers;

use app\components\DynaGridWidget;
use app\helpers\ArrayHelper;
use app\helpers\StringHelper;
use Yii;
use app\models\Users;
use app\models\UsersSearch;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

/**
 * UsersController implements the CRUD actions for Users model.
 */
class UsersController extends ArmsBaseController
{
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['item-by-login'],
		]);
	}
	

	/**
	 * Отображает список пользователей с поиском и фильтрацией.
	 * Решает проблему синхронизации поиска по двум полям ФИО (shortName / Ename):
	 * если поиск задан через `shortName`, он автоматически дублируется в `Ename`
	 * только если соответствующая колонка видима в текущем DynaGrid-профиле.
	 * URL обновляется с исправленными параметрами перед рендером.
	 *
	 * GET-параметры: стандартные параметры поиска UsersSearch (через queryParams),
	 *   в том числе: UsersSearch[shortName], UsersSearch[Ename] и прочие атрибуты.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		/*
		 * Что это за хрень ниже?
		 * У нас есть 2 поля ФИО для вывода в таблице shortName и Ename (причем оба могут быть скрыты)
		 *
		 * Но поиск на главной заполняет только одно поле (short). Может получиться так что в открытой таблице
		 * будут отфильтрованные данные, но не будет видно по какому полю. Вот это мы и пытаемся разрулить
		 */
		$params=Yii::$app->request->queryParams;
		
		$columns=DynaGridWidget::fetchVisibleAttributes(new Users(),StringHelper::class2Id($this->modelClass).'-index');
		
		//если выставлен поиск по short, то добавляем поиск по Ename
		if ($name=ArrayHelper::getTreeValue($params,['UsersSearch','shortName']))
			$params=ArrayHelper::setTreeDefaultValue($params,['UsersSearch','Ename'],$name);
		
		//убираем те параметры, которые не выводятся в таблицу
		if (!DynaGridWidget::tableColumnIsVisible('users-index','Ename'))
			ArrayHelper::unsetTreeValue($params,['UsersSearch','Ename']);
		
		if (!DynaGridWidget::tableColumnIsVisible('users-index','shortName'))
			ArrayHelper::unsetTreeValue($params,['UsersSearch','shortName']);
		
		//обновляем текущий Url с исправленными параметрами
		Yii::$app->request->setUrl(Url::to(['index'],$params));
		
		//дальше все как обычно
		$searchModel = new UsersSearch();
		
		$this->archivedSearchInit($searchModel,$dataProvider,$switchArchivedCount,$columns,$params);
		
		return $this->render('index', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
			'switchArchivedCount' => $switchArchivedCount??null,
			'additionalCreateButton' => $this->additionalCreateButton,
			'additionalToolButton' => $this->additionalToolButton,
		]);
	}
	
	
	/**
	 * Отображает карточку пользователя, найденного по логину (поле `Login`).
	 * Рендерит partial-view «item».
	 *
	 * GET-параметры:
	 * @param string $login  Логин пользователя (Users.Login)
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если пользователь не найден
	 */
	public function actionItemByLogin($login)
	{
		if (($model = Users::findOne(['Login'=>$login])) !== null) {
			return $this->renderPartial('item', ['model' => $model]);
		}
		
		throw new NotFoundHttpException('The requested page does not exist.');
	}
	/**
	 * Тестирует actionItemByLogin: запрашивает карточку пользователя с логином 'guest'.
	 * Пользователь 'guest' присутствует в окружении по умолчанию (гостевой аккаунт Yii2).
	 * Ожидает HTTP 200.
	 *
	 * @return array
	 */
	public function testItemByLogin(): array
	{
		return [[
			'name' => 'default',
			'GET' => ['login' => 'guest'],
			'response' => 200,
		]];
	}
	public $modelClass=Users::class;
	
	/**
	 * Отображает карточку пользователя, найденного по полному имени (поле `Ename`).
	 * Рендерит partial-view «item».
	 *
	 * GET-параметры:
	 * @param string $name  Полное ФИО пользователя (Users.Ename)
	 *
	 * @return mixed
	 * @throws NotFoundHttpException если пользователь не найден
	 */
	public function actionItemByName($name)
	{
		if (($model = Users::findOne(['Ename'=>$name])) !== null) {
			return $this->renderPartial('item', ['model' => $model]);
		}
		
		throw new NotFoundHttpException('The requested page does not exist.');
	}
	
}
