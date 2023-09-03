<?php

namespace app\controllers;

use app\components\DynaGridWidget;
use app\helpers\ArrayHelper;
use Yii;
use app\models\Users;
use app\models\UsersSearch;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * UsersController implements the CRUD actions for Users model.
 */
class UsersController extends ArmsBaseController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
		$behaviors=[
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'delete' => ['POST'],
				],
			],
		];
		if (!empty(Yii::$app->params['useRBAC'])) $behaviors['access']=[
			'class' => \yii\filters\AccessControl::className(),
			'rules' => [
				['allow' => true, 'actions'=>['create','update','delete','unlink'], 'roles'=>['editor']],
				['allow' => true, 'actions'=>['index','view','ttip','validate','item','item-by-name','item-by-login'], 'roles'=>['@','?']],
			],
			'denyCallback' => function ($rule, $action) {
				throw new  \yii\web\ForbiddenHttpException('Access denied');
			}
		];
		return $behaviors;
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
		
		//загружаем видимые колонки для пользовательской таблицы
		$visibleColumns=DynaGridWidget::fetchVisibleColumns('users-index');
		//если они загрузились
		if (!is_null($visibleColumns)) {
			//убираем те параметры, которые не выводятся в таблицу
			if (!DynaGridWidget::columnIsVisible('Ename',$visibleColumns))
				ArrayHelper::setTreeValue($params,['UsersSearch','Ename'],null);
			
			if (!DynaGridWidget::columnIsVisible('shortName',$visibleColumns))
				ArrayHelper::setTreeValue($params,['UsersSearch','shortName'],null);
		}
		
		//обновляем текущий Url с исправленными параметрами
		Yii::$app->request->setUrl(Url::to(['index'],$params));
		
		//дальше все как обычно
		$searchModel = new UsersSearch();
		$dataProvider = $searchModel->search($params);
		return $this->render('index', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
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



    /**
     * Deletes an existing Users model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }
    
    public function actionAssignRole($id)
    {
	    if (Yii::$app->request->isAjax) {
		    return $this->renderAjax('create', [
			    'model' => $model,
		    ]);
	    } else {
		    return $this->render('create', [
			    'model' => $model,
		    ]);
	    }    }
	
	/**
     * Finds the Users model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Users the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Users::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
