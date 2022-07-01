<?php

namespace app\controllers;

use Yii;
use app\models\Contracts;
use app\models\ContractsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\bootstrap5\ActiveForm;
use yii\web\Response;
use yii\web\UploadedFile;


/**
 * ContractsController implements the CRUD actions for Contracts model.
 */
class ContractsController extends Controller
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
				['allow' => true, 'actions'=>['create','update','update-form','delete','unlink','unlink-arm','unlink-tech','link-arm','link-tech','scan-upload'], 'roles'=>['editor']],
				['allow' => true, 'actions'=>['index','view','ttip','hint-arms','hint-parent','scans','validate'], 'roles'=>['@','?']],
			],
			'denyCallback' => function ($rule, $action) {
				throw new  \yii\web\ForbiddenHttpException('Access denied');
			}
		];
		return $behaviors;
    }

    /**
     * Lists all Contracts models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ContractsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		$this->view->params['layout-container'] = 'container-fluid';

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
	
	/**
	 * Возвращает IDs армов с переданными документами
	 * @param $ids
	 * @param $form
	 * @return mixed
	 */
	public function actionHintArms($ids,$form)
	{
		return Yii::$app->formatter->asRaw(Contracts::fetchArmsHint($ids,$form));
	}
	
	/**
	 * Возвращает IDs армов с переданными документами
	 * @param $ids
	 * @param $form
	 * @return mixed
	 */
	public function actionHintParent($ids,$form)
	{
		return Yii::$app->formatter->asRaw(Contracts::fetchParentHint($ids,$form));
	}
	
	/**
	 * Displays a single Contracts model.
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
	 * Displays a single Contracts model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionView($id)
	{
		return $this->render('view', [
			'model' => $this->findModel($id),
		]);
	}

	/**
	 * Displays a single Contracts model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionScans($id)
	{
		return $this->renderAjax('scans', [
			'model' => $this->findModel($id),
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
			$model = new Contracts();

		if ($model->load(Yii::$app->request->post())) {
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return ActiveForm::validate($model);
		}
	}


	/**
	 * Creates a new Contracts model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
    public function actionCreate()
    {
	    //if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
	
	    $model = new Contracts();
	
		//передали родительский документ
		if ($parent_id=Yii::$app->request->get('parent')) {
			$model->parent_id=$parent_id;
		}
	
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->isAjax) {
				Yii::$app->response->format = Response::FORMAT_JSON;
				return ['error'=>'OK','code'=>0,'model'=>$model];
			} else {
				return $this->redirect([Yii::$app->request->get('apply')?'update':'view', 'id' => $model->id]);
			}
		} elseif (Yii::$app->request->isPost && Yii::$app->request->isAjax) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$result = [];
			foreach ($model->getErrors() as $attribute => $errors) {
				$result[yii\helpers\Html::getInputId($model, $attribute)] = $errors;
			}
			return ['error'=>'ERROR','code'=>1,'validation'=>$result];
			
		} elseif (Yii::$app->request->isAjax) {
			return $this->renderAjax('create', [
				'model' => $model,
			]);
		}
	
	
		return $this->render('create', [
			'model' => $model,
		]);


    }


	/**
	 * Updates an existing Contracts model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdate($id)
	{
		//if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
		
		$model = $this->findModel($id);

		//обработка аякс запросов
		if (Yii::$app->request->isAjax) {
			Yii::$app->response->format = Response::FORMAT_JSON;

			if ($model->load(Yii::$app->request->post()) && $model->save()) {
				return ['error'=>'OK','code'=>0,'model'=>$model];
			} elseif(Yii::$app->request->isPost) {
				$result = [];
				foreach ($model->getErrors() as $attribute => $errors) {
					$result[yii\helpers\Html::getInputId($model, $attribute)] = $errors;
				}
				return ['error'=>'ERROR','code'=>1,'validation'=>$result];
			}
			return $this->renderAjax('update', [
				'model' => $model,
			]);
		}

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->redirect([Yii::$app->request->get('apply')?'update':'view', 'id' => $model->id]);
		}

		return $this->render('update', [
			'model' => $model,
		]);
	}

	/**
	 * Updates an existing Contracts model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdateForm($id)
	{
		return $this->renderAjax('_form', [
			'model' => $this->findModel($id),
		]);
	}

	/**
	 * Deletes an existing Contracts model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @throws \Exception
	 * @throws \Throwable
	 * @throws \yii\db\StaleObjectException
	 */
    public function actionDelete($id)
    {
	    if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
	
	    $model=$this->findModel($id);

    	//ищем и удаляем все привязанные сканы
    	$scans=$model->scans;
    	if (is_array($scans) && count($scans)) {
    		foreach ($scans as $scan) {
    			$scan->delete();
		    }
	    }
        $this->findModel($id)->delete();


        return $this->redirect(['index']);
    }

	public function actionScanUpload()
	{
		$id=Yii::$app->request->post('contracts_id');
		//Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		if (is_null($id))
			return "{\"error\":\"Невозможно прикрепить сканы к еще не созданному документу. Нажмите сначала кнопку &quot;Применить&quot;\"}";
		else
			return "{\"error\":\"Якобы сохранено в модель $id\"}";	}

	/**
	 * Отвязывает документ от объекта.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id
	 * @param integer $arms_id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUnlinkArm($id,$arms_id)
	{
		if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
		
		
		$usage=false;
		$usage_deleted=false;

		$model=$this->findModel($id);
		$arms_ids=$model->arms_ids;
		if (array_search($arms_id,$arms_ids)!==false) {
			$usage=true;
			$model->arms_ids=array_diff($arms_ids,[$arms_id]);
			if ($model->save()) $usage_deleted=true;
		}

		Yii::$app->response->format = Response::FORMAT_JSON;
		if ($usage) {
			if ($usage_deleted) {
				return ['error'=>'OK','code'=>'0','Message'=>'Usage removed'];
			} else {
				return ['error'=>'ERROR','code'=>'1','Message'=>'Link removing error'];
			}
		} else {
			return ['error'=>'OK','code'=>'2','Message'=>'Requested usage not found ['.implode(',',$arms_ids).']'];
		}
	}

	/**
	 * Отвязывает документ от объекта.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id
	 * @param integer $techs_id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUnlinkTech($id,$techs_id)
	{
		if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
		
		$usage=false;
		$usage_deleted=false;

		$model=$this->findModel($id);
		$techs_ids=$model->techs_ids;
		if (array_search($techs_id,$techs_ids)!==false) {
			$usage=true;
			$model->techs_ids=array_diff($techs_ids,[$techs_id]);
			if ($model->save()) $usage_deleted=true;
		}

		Yii::$app->response->format = Response::FORMAT_JSON;
		if ($usage) {
			if ($usage_deleted) {
				return ['error'=>'OK','code'=>'0','Message'=>'Usage removed'];
			} else {
				return ['error'=>'ERROR','code'=>'1','Message'=>'Link removing error'];
			}
		} else {
			return ['error'=>'OK','code'=>'2','Message'=>'Requested usage not found ['.implode(',',$techs_ids).']'];
		}
	}

	/**
	 * Отвязывает документ от объекта.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id
	 * @param integer $arms_id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionLinkArm($id,$arms_id)
	{
		if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
		
		$model=$this->findModel($id);
		$arms_ids=$model->arms_ids;
		if (array_search($arms_id,$arms_ids)===false) {
			$arms_ids[]=$arms_id;
			$model->arms_ids=$arms_ids;
			$model->save();
		}

		Yii::$app->response->format = Response::FORMAT_JSON;
		return ['error'=>'OK','code'=>'0','Message'=>'Added'];

	}

	/**
	 * Отвязывает документ от объекта.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id
	 * @param integer $arms_id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionLinkTech($id,$techs_id)
	{
		if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
		
		$model=$this->findModel($id);
		$techs_ids=$model->techs_ids;
		if (array_search($techs_id,$techs_ids)===false) {
			$techs_ids[]=$techs_id;
			$model->techs_ids=$techs_ids;
			$model->save();
		}

		Yii::$app->response->format = Response::FORMAT_JSON;
		return ['error'=>'OK','code'=>'0','Message'=>'Added'];

	}

	/**
     * Finds the Contracts model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Contracts the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Contracts::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
