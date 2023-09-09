<?php

namespace app\controllers;

use Yii;
use app\models\NetIps;
use app\models\NetIpsSearch;
use yii\data\ArrayDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\bootstrap5\ActiveForm;


/**
 * NetIpsController implements the CRUD actions for NetIps model.
 */
class NetIpsController extends Controller
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
				['allow' => true, 'actions'=>['create','update','delete',], 'roles'=>['editor']],
				['allow' => true, 'actions'=>['index','view','ttip','validate','item-by-name'], 'roles'=>['@','?']],
			],
			'denyCallback' => function ($rule, $action) {
				throw new  \yii\web\ForbiddenHttpException('Access denied');
			}
		];
		return $behaviors;
	}

    /**
     * Lists all NetIps models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new NetIpsSearch();
        //$dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $dataProvider = new ArrayDataProvider([
        	'allModels'=>$searchModel->search(Yii::$app->request->queryParams)->models,
			'pagination'=>['pageSize'=>100],
			'sort'=> [
				'defaultOrder' => ['text_addr'=>SORT_ASC],
				'attributes'=>[
					'text_addr'=>[
						'asc'=>['addr'=>SORT_ASC],
						'desc'=>['addr'=>SORT_DESC],
					],
					'network'=>[
						'asc'=>['network.addr'=>SORT_ASC],
						'desc'=>['network.addr'=>SORT_DESC],
					],
					'vlan'=>[
						'asc'=>['network.netVlan.vlan'=>SORT_ASC],
						'desc'=>['network.netVlan.vlan'=>SORT_DESC],
					],
					'comment'
				]
			]
		]);
	
		$networkProvider=null;
        if (!$dataProvider->totalCount && ($ip_addr=$searchModel->text_addr)) {
			$ip=new NetIps(['text_addr'=>$ip_addr]);
			if ($ip->validate()) { //если тут валидный адрес, то можно подобрать сетку
				$ip->beforeSave(true);
				if (is_object($ip->network)) {
					$networkProvider= new ArrayDataProvider([
						'allModels'=>[$ip->network],
						'pagination'=>false,
					]);
				}
			}
		}
        
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
			'networkProvider' => $networkProvider,
        ]);
    }
	public function actionItemByName($name)
	{
		if (($model = NetIps::findOne(['text_addr' => $name])) !== null) {
			return $this->renderPartial('item', ['model' => $model, 'static_view' => true]);
		}
		throw new NotFoundHttpException('The requested page does not exist.');
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
			$model = new NetIps();

		if ($model->load(Yii::$app->request->post())) {
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return ActiveForm::validate($model);
		}
	}


	/**
	* Displays a item for single model.
	* @param integer $id
	* @return mixed
	* @throws NotFoundHttpException if the model cannot be found
	*/
	public function actionItem($id)
	{
		return $this->renderPartial('item', [
			'model' => $this->findModel($id)
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
     * Displays a single NetIps model.
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
     * Creates a new NetIps model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new NetIps();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
            return $this->redirect(['view', 'id' => $model->id]);
        }
        
		$model->text_addr=Yii::$app->request->get('text_addr',null);
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing NetIps model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing NetIps model.
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
     * Finds the NetIps model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return NetIps the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = NetIps::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
