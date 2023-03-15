<?php

namespace app\controllers;

use app\helpers\FieldsHelper;
use app\models\Comps;
use Yii;
use app\models\TechModels;
use app\models\TechModelsSearch;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\bootstrap5\ActiveForm;
use yii\web\Response;

/**
 * TechModelsController implements the CRUD actions for TechModels model.
 */
class TechModelsController extends ArmsBaseController
{
	
	public $modelClass='app\models\TechModels';
	
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
					'render-rack' => ['POST'],
			    ],
		    ]
	    ];
	    if (!empty(Yii::$app->params['useRBAC'])) $behaviors['access']=[
		    'class' => \yii\filters\AccessControl::className(),
		    'rules' => [
			    ['allow' => true, 'actions'=>['create','update','uploads','delete','unlink'], 'roles'=>['editor']],
			    ['allow' => true, 'actions'=>['index','view','ttip','validate','hint-comment','hint-template','hint-description','item','item-by-name'], 'roles'=>['@','?']],
		    ],
		    'denyCallback' => function ($rule, $action) {
			    throw new  \yii\web\ForbiddenHttpException('Access denied');
		    }
	    ];
	    return $behaviors;
    }
	
	
	/**
	 * Displays a item for single model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionItem($id,$long=false)
	{
		return $this->renderPartial('item', [
			'model'	=> $this->findModel($id),
			'long'	=> $long,
		]);
	}
	
	
	public function actionItemByName($name,$manufacturer,$long=false)
	{
		/// производитель
		//ищем в словаре
		if (is_null($man_id=\app\models\ManufacturersDict::fetchManufacturer($manufacturer))) {
			//ищем в самих производителях
			if (!is_object($man_obj = \app\models\Manufacturers::findOne(['name'=>$manufacturer]))) {
				throw new NotFoundHttpException('Requested manufacturer not found');
			} else {
				$man_id=$man_obj->id;
			}
		}
		
		if (($model = TechModels::findOne(['name'=>$name,'manufacturers_id'=>$man_id])) !== null) {
			return $this->renderPartial('item', [
				'model' => $model,
				'long'	=> $long,
			]);
		}
		throw new NotFoundHttpException('The requested model not found within that manufacturer');
	}
	
	
	
	/**
	 * Подсказка по заполнению спеки (берется из типа модели)
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionHintTemplate($id)
	{
		$model=$this->findModel($id);
		if ($model->individual_specs)
			return Yii::$app->formatter->asNtext($model->type->comment);
		else
			return \app\models\TechModels::$no_specs_hint;
	}
	
	/**
	 * Информация о модели
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionHintDescription($id)
	{
		$model=$this->findModel($id);
		return Yii::$app->formatter->asNtext($model->comment);
	}
	
	
	
	public function actionHintComment($id){
		Yii::$app->response->format = Response::FORMAT_JSON;
		$data=\app\models\TechModels::fetchTypeComment($id);
		if (!is_array($data)) throw new NotFoundHttpException('The requested data does not exist.');
		//переоформляем под qtip
		$data['hint']=FieldsHelper::toolTipOptions($data['name'],$data['hint'])['qtip_ttip'];
		return $data;
	}
	
	
	/**
	 * Displays a single TechModels model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionView($id)
	{
		$this->setQueryParam(['TechsSearch'=>['model_id'=>$id]]);
		
		$techSearchModel = new \app\models\TechsSearch();
		$techDataProvider = $techSearchModel->search(Yii::$app->request->queryParams);
		
		return $this->render('view', [
			'model' => $this->findModel($id),
			'searchModel' => $techSearchModel,
			'dataProvider' => $techDataProvider,
		]);
	}

	/**
	 * Displays a single TechModels model.
	 * @param string $config
	 * @return mixed
	 */
	public function actionRenderRack()
	{
		return \app\components\RackWidget::widget(
			json_decode(
				Yii::$app->request->getBodyParam('config'),true
			)
		);
	}
	
	
	/**
     * Creates a new TechModels model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new TechModels();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
	        if (Yii::$app->request->isAjax) {
		        Yii::$app->response->format = Response::FORMAT_JSON;
		        return $model;
	        }
	
			if (Yii::$app->request->get('return')=='previous')
				return $this->redirect(Url::previous());
	        return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }
	
	/**
	 * Updates an existing TechModels model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id);
		
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->get('return')=='previous')
				return $this->redirect(Url::previous());
			return $this->redirect(['view', 'id' => $model->id]);
		}
		
		return $this->render('update', [
			'model' => $model,
		]);
	}
	
	/**
	 * Updates an existing TechModels model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUploads($id)
	{
		$model = $this->findModel($id);
		return $this->render('uploads', [
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
			$model = new \app\models\TechModels();

		if ($model->load(Yii::$app->request->post())) {
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return ActiveForm::validate($model);
		}
	}


    /**
     * Deletes an existing TechModels model.
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
     * Finds the TechModels model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return TechModels the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = TechModels::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
