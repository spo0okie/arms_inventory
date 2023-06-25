<?php

namespace app\controllers;

use app\helpers\ArrayHelper;
use Yii;
use app\models\Soft;
use app\models\SoftSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\Response;

/**
 * SoftController implements the CRUD actions for Soft model.
 */
class SoftController extends Controller
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
		    ]
	    ];
	    if (!empty(Yii::$app->params['useRBAC'])) $behaviors['access']=[
		    'class' => \yii\filters\AccessControl::className(),
		    'rules' => [
			    ['allow' => true, 'actions'=>['create','update','delete','unlink','select-update'], 'roles'=>['editor']],
			    ['allow' => true, 'actions'=>['index','view','ttip','validate'], 'roles'=>['@','?']],
		    ],
		    'denyCallback' => function ($rule, $action) {
			    throw new  \yii\web\ForbiddenHttpException('Access denied');
		    }
	    ];
	    return $behaviors;
    }

    /**
     * Lists all Soft models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SoftSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
	
	/**
	 * Displays a single Soft model ttip.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionTtip($id,$hitlist=null)
	{
		return $this->renderPartial('ttip', [
			'model' => $this->findModel($id),
			'hitlist' => $hitlist
		]);
	}

	/**
	 * Displays a single Soft model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionView($id)
	{
		$searchModel = new \app\models\CompsSearch();
		$dataProvider = $searchModel->search(ArrayHelper::recursiveOverride(Yii::$app->request->queryParams,['CompsSearch'=>['softHits_ids'=>$id]]));
		
		$licSearchModel = new \app\models\LicGroupsSearch();
		$licProvider = $licSearchModel->search(ArrayHelper::recursiveOverride(Yii::$app->request->queryParams,['LicGroupsSearch'=>['soft_ids'=>$id]]));
		
		return $this->render('view', [
			'model' => $this->findModel($id),
			'searchModel'=>$searchModel,
			'dataProvider'=>$dataProvider,
			'licProvider'=>$licProvider,
		]);
	}
	
	
	/**
     * Creates a new Soft model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Soft();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $model->manufacturers_id=Yii::$app->request->get('manufacturers_id');
        $descr=Yii::$app->request->get('descr');
        $cut=is_object($model->manufacturer)?$model->manufacturer->cutManufacturer($descr):'';
        if ($cut) $descr=trim(substr($descr,$cut));
        $model->descr=$descr;

        $model->items=Yii::$app->request->get('items');


        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Soft model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(array_merge(Yii::$app->request->get(),Yii::$app->request->post())) && $model->save()) {
            if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $model->addItem(Yii::$app->request->get('items'));


        return $this->render('update', [
            'model' => $model,
        ]);
    }
	
	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionSelectUpdate($name,$manufacturers_id=null)
	{
		if (!is_null($manufacturers_id))
			//для случаев, если производитель определен, выводим его продукты (только названия самих продуктов)
			$items=\yii\helpers\ArrayHelper::map(\app\models\Soft::fetchBy(['manufacturers_id'=>$manufacturers_id]),'id','descr');
		else
			//в ином случае выводим список всех продуктов с указанием производителя в названии
			$items=\app\models\Soft::listItemsWithPublisher();
		
		
		return Yii::$app->request->isAjax?
			$this->renderAjax( '/soft/_search_by_name_to_update',
			[
				'addItems'=>$name,
				'items'=>$items,
				'modalParent' => '#modal_form_loader'
			]):
			$this->render('/soft/_search_by_name_to_update',
			[
				'addItems'=>$name,
				'items'=>$items,
			]);
	}
    
    /**
     * Deletes an existing Soft model.
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
     * Finds the Soft model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Soft the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Soft::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
