<?php

namespace app\controllers;

use app\models\Techs;
use app\models\Arms;
use Yii;
use app\models\Ports;
use app\models\PortsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\bootstrap5\ActiveForm;
use yii\web\Response;


/**
 * PortsController implements the CRUD actions for Ports model.
 */
class PortsController extends ArmsBaseController
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
				['allow' => true, 'actions'=>['create','update','delete','port-list',], 'roles'=>['editor']],
				['allow' => true, 'actions'=>['index','view','ttip','validate'], 'roles'=>['@','?']],
			],
			'denyCallback' => function ($rule, $action) {
				throw new  \yii\web\ForbiddenHttpException('Access denied');
			}
		];
		return $behaviors;
	}

    /**
     * Lists all Ports models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PortsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
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
			$model = new Ports();

		if ($model->load(Yii::$app->request->post())) {
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return ActiveForm::validate($model);
		}
	}
	

	public function actionCreate() {
		return $this->actionUpdate(null);
	}
	
    /**
     * Updates an existing Ports model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = is_null($id)?
			$model=new Ports():
			$this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
        	if ($model->save()) {
				if (Yii::$app->request->isAjax) {
					Yii::$app->response->format = Response::FORMAT_JSON;
					return [$model];
				}  else {
					if (Yii::$app->request->get('return') == 'previous')
						return $this->redirect(Url::previous());
					else
						return $this->redirect(['view', 'id' => $model->id]);
				}
			} else {
				//тут у нас интересная логика. Если валидация прошла, а сохранение нет
				//значит мы сами отказались сохранять в beforeSave
				//значит сохраняемая информация не уникальная (пустая/шаблонная)
				if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
				return $this->redirect(['ports/index']);
			}
        }
	
		$model->load(Yii::$app->request->get());
	
		return Yii::$app->request->isAjax?
			$this->renderAjax('update', [
				'model' => $model,
				'modalParent' => '#modal_form_loader'
			]):
			$this->render('update', [
				'model' => $model,
			]);
    }

    /**
     * Deletes an existing Ports model.
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
	 * Returns tech available network ports
	 * @return array
	 */
	public function actionPortList()
	{
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		if (isset($_POST['depdrop_all_params'])) {
			$params = $_POST['depdrop_all_params'];
			if (is_array($params)) {
				if (isset($params['link_techs_id']) && strlen($params['link_techs_id'])) {
					$model=Techs::findOne($params['link_techs_id']);
					return ['output'=>$model->ddPortsList, 'selected'=>''];
				} elseif (isset($params['link_arms_id']) && strlen($params['link_arms_id'])) {
					$model=Arms::findOne($params['link_arms_id']);
					return ['output'=>$model->ddPortsList, 'selected'=>''];
				} else {
					return ['output'=>[], 'selected'=>''];
				}
				
				//$out = self::getSubCatList($cat_id);
				// the getSubCatList function will query the database based on the
				// cat_id and return an array like below:
				// [
				//    ['id'=>'<sub-cat-id-1>', 'name'=>'<sub-cat-name1>'],
				//    ['id'=>'<sub-cat_id_2>', 'name'=>'<sub-cat-name2>']
				// ]
			}
		}
		return ['output'=>'', 'selected'=>''];
	}
    /**
     * Finds the Ports model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Ports the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id,$failRoute=null)
    {
        if (($model = Ports::findOne($id)) !== null) {
            return $model;
        }
		
        if (!is_null($failRoute)) {
			$this->redirect($failRoute);
		}
        
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
