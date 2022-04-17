<?php

namespace app\controllers;

use Yii;
use app\models\LicItems;
use app\models\LicItemsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\bootstrap5\ActiveForm;
use yii\data\ActiveDataProvider;


/**
 * LicItemsController implements the CRUD actions for LicItems model.
 */
class LicItemsController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
	    $behaviors=[
		    'verbs' => [
			    'class' => VerbFilter::className(),
			    'actions' => [
				    'delete' => ['POST'],
			    ],
		    ]
	    ];
	    if (!empty(Yii::$app->params['useRBAC'])) $behaviors['access']=[
		    'class' => \yii\filters\AccessControl::className(),
		    'rules' => [
			    ['allow' => true, 'actions'=>['create','update','delete','unlink'], 'roles'=>['editor']],
			    ['allow' => true, 'actions'=>['index','view','ttip','validate'], 'roles'=>['@','?']],
		    ],
		    'denyCallback' => function ($rule, $action) {
			    throw new  \yii\web\ForbiddenHttpException('Access denied');
		    }
	    ];
	    return $behaviors;
    }


	/**
	 * Возвращает IDs армов связанных с закупкой (через документы)
	 * @param $id
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionHintArms($id,$form)
	{
		//Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		if ($model=$this->findModel($id)) {
			return Yii::$app->formatter->asRaw(\app\models\Contracts::fetchArmsHint($model->contracts_ids,$form));
		};
		return null;
	}

    /**
     * Lists all LicItems models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new LicItemsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

	/**
	 * Displays a tooltip for single model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionTtip($id)
	{
		return $this->renderPartial('ttip', [
			'model' => $this->findModel($id),
		]);
	}


	/**
	 * Displays a single Arms model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionContracts($id)
	{
		return $this->renderAjax('contracts', ['model' => $this->findModel($id),]);
	}


	/**
     * Displays a single LicItems model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
	        'keys' => new ActiveDataProvider([
		        'query' => \app\models\LicKeys::find()->where(['lic_items_id'=>$id]),
	        ])
        ]);
    }

    /**
     * Creates a new LicItems model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new LicItems();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($group=Yii::$app->request->get('lic_group_id')) $model->lic_group_id=$group;

        return $this->render('create', [
            'model' => $model,
        ]);
    }

	/**
	 * Validates  model on update.
	 * @param null $id
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionValidate($id=null)
	{
		if (!is_null($id))
			$model = $this->findModel($id);
		else
			$model = new LicItems();

		if ($model->load(Yii::$app->request->post())) {
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return ActiveForm::validate($model);
		}
	}

    /**
     * Updates an existing LicItems model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing LicItems model.
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

	/**
	 * Удаляем АРМ или софт из лицензии
	 * @param $id
	 * @return string|\yii\web\Response
	 * @throws NotFoundHttpException
	 */
	public function actionUnlink($id,$soft_id=null,$arms_id=null,$users_id=null,$comps_id=null){
		$model = $this->findModel($id);
		$updated = false;

		//если нужно отстегиваем софт
		if (!is_null($soft_id)) {
			$model->soft_ids=array_diff($model->soft_ids,[$soft_id]);
			$updated=true;
		}
		
		//если нужно то АРМ
		if (!is_null($arms_id)) {
			$model->arms_ids=array_diff($model->arms_ids,[$arms_id]);
			$updated=true;
		}
		
		//если нужно то Комп
		if (!is_null($comps_id)) {
			$model->comps_ids=array_diff($model->comps_ids,[$comps_id]);
			$updated=true;
		}
		
		//если нужно то Пользователя
		if (!is_null($users_id)) {
			$model->users_ids=array_diff($model->users_ids,[$users_id]);
			$updated=true;
		}
		
		//сохраняем
		if ($updated) $model->save();

		return $this->redirect(['view', 'id' => $model->id]);
	}


    /**
     * Finds the LicItems model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return LicItems the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = LicItems::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
