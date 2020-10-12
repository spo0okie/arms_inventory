<?php

namespace app\controllers;

use Yii;
use app\models\Arms;
use app\models\ArmsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\bootstrap\ActiveForm;
use yii\web\Response;
use yii\helpers\Url;

/**
 * ArmsController implements the CRUD actions for Arms model.
 */
class ArmsController extends Controller
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
				['allow' => true, 'actions'=>['create','create-apply','update','update-apply','delete','unlink','updhw','rmhw'], 'roles'=>['admin']],
				['allow' => true, 'actions'=>['index','view','ttip','ttip-hw','validate','contracts'], 'roles'=>['@','?']],
			],
			'denyCallback' => function ($rule, $action) {
				throw new  \yii\web\ForbiddenHttpException('Access denied');
			}
		];
		return $behaviors;
    }

    /**
     * Lists all Arms models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ArmsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
	    
        $this->view->params['layout-container'] = 'container-fluid';
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
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
	 * Displays a tooltip for single model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionTtipHw($id)
	{
		return $this->renderPartial('ttip-hw', [
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
	 * Displays a single Arms model.
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
            $model = new Arms();

        if ($model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }
    }


	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionCreate()
	{
		//if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}

		$model = new Arms();

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->isAjax) {
				Yii::$app->response->format = Response::FORMAT_JSON;
				return [$model];
			}  else {
				//if (Yii::$app->request->get('return')=='previous')
					return $this->redirect(Url::previous());
				//return $this->redirect(['view', 'id' => $model->id]);
			}
		}


		$model->user_id=Yii::$app->request->get('user_id');
		return $this->render('create', [
			'model' => $model,
		]);
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionCreateApply()
	{
		//if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}

		$model = new Arms();

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->redirect(['update', 'id' => $model->id]);
		}

		return $this->render('create', [
			'model' => $model,
		]);
	}

	/**
     * Updates an existing model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
	    //if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}

        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return [$model];
            }  else {
	            if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }


        return $this->render('update', [
            'model' => $model,
        ]);
    }

	/**
	 * Updates an existing model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdateApply($id)
	{
		//if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}

		$model = $this->findModel($id);

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
				return $this->redirect(['view', 'id' => $model->id]);
		}

		return $this->render('update', [
			'model' => $model,
		]);
	}


	/**
	 * Deletes an existing Arms model.
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
	    //if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}

        $this->findModel($id)->delete();

	    if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
        return $this->redirect(['index']);
    }

    /**
     * Finds the Arms model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Arms the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Arms::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Обновляем элемент оборудования
     * @param $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdhw($id){
	    //if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}

        $model = $this->findModel($id);

        //проверяем передан ли uid
        $uid=Yii::$app->request->get('uid',null);
        if (strlen($uid)) {
            if ($uid==='sign-all') { //специальная комманда на подпись всего оборудования
	            //error_log('signin all');
                $model->hwList->signAll();
            }else {
                $newItem = new \app\models\HwListItem();
                $newItem->loadArr($_GET);
                $model->hwList->add($newItem);
            }
            //error_log('savin');
            if (!$model->save()) error_log(print_r($model->errors,true));
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Удаляем элемент оборудования
     * @param $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionRmhw($id){
	    //if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}

        $model = $this->findModel($id);

        //проверяем передан ли uid
        if (strlen(Yii::$app->request->get('uid',null))) {
            $model->hwList->del(Yii::$app->request->get('uid'));
            $model->save();
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }
}
