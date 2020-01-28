<?php

namespace app\controllers;

use Yii;
use app\models\Comps;
use app\models\CompsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * CompsController implements the CRUD actions for Comps model.
 */
class CompsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Comps models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CompsSearch();
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
     * Displays a single Comps model.
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
     * Creates a new Comps model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
	    if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
	
	    $model = new Comps();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $model->arm_id=Yii::$app->request->get('arms_id',$model->arm_id);

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Comps model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
	    if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
	
	    $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
	        if (Yii::$app->request->isAjax) {
		        Yii::$app->response->format = Response::FORMAT_JSON;
		        return [$model];
	        }  else {
		        return $this->redirect(['view', 'id' => $model->id]);
	        }
        }

        $model->arm_id=Yii::$app->request->get('arms_id',$model->arm_id);

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Comps model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
	    if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
	
	    $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Comps model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Comps the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Comps::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }


	/**
	 * Обновляем элементы ПО
	 * @param $id
	 * @return string|\yii\web\Response
	 * @throws NotFoundHttpException
	 */
	public function actionAddsw($id){
		if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
		
		$model = $this->findModel($id);

		//проверяем передан ли a
		$strItems=Yii::$app->request->get('items',null);
		if (strlen($strItems)) {
			if ($strItems==='sign-all') {
				$items=array_keys($model->swList->getAgreed());
			} else
				($items=explode(',',$strItems));

			if (is_array($items)) {
				$model->soft_ids=array_unique(array_merge($model->soft_ids,$items));
				$model->save();
			};
		}

		return $this->redirect(['/arms/view', 'id' => $model->arm_id]);
	}

    /**
     * Удаляем элементы ПО
     * @param $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionRmsw($id){
	    if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
	
	    $model = $this->findModel($id);

        //проверяем передан ли a
        $strItems=Yii::$app->request->get('items',null);
        if (strlen($strItems)) {
            if (is_array($items=explode(',',$strItems))){
                $model->soft_ids=array_diff($model->soft_ids,$items);
                $model->save();
            };
        }

        return $this->redirect(['/arms/view', 'id' => $model->arm_id]);
    }
	/**
	 * Обновляем  список скртытых IP
	 * @param $id
	 * @param $ip
	 * @return string|\yii\web\Response
	 * @throws NotFoundHttpException
	 */
	public function actionIgnoreip($id,$ip){
		if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
		
		$model = $this->findModel($id);

		$ignored=explode("\n",$model->ip_ignore);
		$ignored[]=$ip;
		$model->ip_ignore=implode("\n",array_unique($ignored));
		$model->save();

		return $this->redirect(['/comps/view', 'id' => $model->id]);
	}

	/**
	 * Обновляем  список скртытых IP
	 * @param $id
	 * @param $ip
	 * @return string|\yii\web\Response
	 * @throws NotFoundHttpException
	 */
	public function actionUnignoreip($id,$ip){
		if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
		
		$model = $this->findModel($id);

		$ignored=explode("\n",$model->ip_ignore);
		$id=array_search($ip,$ignored);
		if (!is_null($id)) {
			unset($ignored[$id]);
			$model->ip_ignore=implode("\n",array_unique($ignored));
			$model->save();
		};

		return $this->redirect(['/comps/view', 'id' => $model->id]);
	}

}
