<?php

namespace app\controllers;

use app\components\DynaGridWidget;
use app\helpers\ArrayHelper;
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
	public $modelClass=Users::class;
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['item-by-login'],
		]);
	}
	

	/**
	 * Lists all Users models.
	 * @return mixed
	 */
	public function actionIndex()
	{
		/*
		 * Что это за хрень ниже?
		 * У нас есть 2 поля ФИО для вывода в таблице shortName и Ename (причем оба могут быть скрыты)
		 *
		 * Но поиск на главной заполняет только одно поле (short). Может получиться так что в открытой таблице
		 * будут отфильтрованные денные но не будет видно по какому полю. Вот это мы и пытаемся разрулить
		 */
		$params=Yii::$app->request->queryParams;
		
		//если выставлен поиск по short, то добавляем поиск по Ename
		if ($name=ArrayHelper::getTreeValue($params,['UsersSearch','shortName']))
			$params=ArrayHelper::setTreeDefaultValue($params,['UsersSearch','Ename'],$name);
		
		//убираем те параметры, которые не выводятся в таблицу
		if (!DynaGridWidget::tableColumnIsVisible('users-index','Ename'))
			ArrayHelper::setTreeValue($params,['UsersSearch','Ename'],null);
		
		if (!DynaGridWidget::tableColumnIsVisible('users-index','shortName'))
			ArrayHelper::setTreeValue($params,['UsersSearch','shortName'],null);
		
		//обновляем текущий Url с исправленными параметрами
		Yii::$app->request->setUrl(Url::to(['index'],$params));
		
		//дальше все как обычно
		$searchModel = new UsersSearch();
		$searchModel->archived= Yii::$app->request->get('showArchived',false);
		$dataProvider = $searchModel->search($params);
		
		//ищем тоже самое но с дочерними в противоположном положении
		$switchArchived=clone $searchModel;
		$switchArchived->archived=!$switchArchived->archived;
		$switchArchivedCount=$switchArchived->search(Yii::$app->request->queryParams)->totalCount;
		
		return $this->render('index', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
			'switchArchivedCount' => $switchArchivedCount,
		]);
	}
	
	
	public function actionItemByLogin($login)
	{
		if (($model = Users::findOne(['Login'=>$login])) !== null) {
			return $this->renderPartial('item', ['model' => $model]);
		}
		
		throw new NotFoundHttpException('The requested page does not exist.');
	}
	
	public function actionItemByName($name)
	{
		if (($model = Users::findOne(['Ename'=>$name])) !== null) {
			return $this->renderPartial('item', ['model' => $model]);
		}
		
		throw new NotFoundHttpException('The requested page does not exist.');
	}
	
	/**
	 * Lists all Users models.
	 * @return mixed
	 */
	public function actionLogins()
	{
		$searchModel = new UsersSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);

		return $this->render('logins', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}

}
