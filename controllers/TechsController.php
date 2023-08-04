<?php

namespace app\controllers;

use Yii;
use app\models\Techs;
use app\models\TechsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\bootstrap5\ActiveForm;
use yii\web\Response;
use yii\helpers\Url;

/**
 * TechsController implements the CRUD actions for Techs model.
 */
class TechsController extends ArmsBaseController
{
	public $modelClass='app\models\Techs';
	
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
			    ['allow' => true, 'actions'=>['create','update','uploads','delete','unlink','updhw','rmhw','edithw','port-list'], 'roles'=>['editor']],
			    ['allow' => true, 'actions'=>['index','view','ttip','ttip-hw','validate','inv-num','item','item-by-name','passport'], 'roles'=>['@','?']],
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
	public function actionItem($id)
	{
		return $this->renderPartial('item', [
			'model'	=> $this->findModel($id),
		]);
	}
	
	
	public function actionItemByName($name)
	{
		if (($model = Techs::findOne(['num'=>$name])) !== null) {
			return $this->renderPartial('item', [
				'model' => $model,
			]);
		}
		throw new NotFoundHttpException('The requested tech not found');
	}

	/**
	 * Displays a single OrgPhones model.
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
	 * Displays a tooltip for hw of single model.
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
	 * Формирует префикс и возвращает следующий инвентарный номер в этом префиксе
	 * @param null|integer $model_id
	 * @param null|integer $place_id
	 * @param null|integer $org_id
	 * @param null|integer $arm_id
	 * @param null|integer $installed_id
	 * @return mixed
	 */
	public function actionInvNum($model_id=null,$place_id=null,$org_id=null,$arm_id=null,$installed_id=null)
	{
		$prefix=Techs::genInvPrefix($model_id,$place_id,$org_id,$arm_id,$installed_id);
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		return Techs::fetchNextNum($prefix);
	}


    /**
     * Displays a single Techs model.
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
     * Displays a single Techs model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionPassport($id)
    {
        return $this->render('passport', [
            'model' => $this->findModel($id),
        ]);
    }


    /**
     * Updates an existing Techs model.
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
			$model = new \app\models\Techs();

		if ($model->load(Yii::$app->request->post())) {
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return ActiveForm::validate($model);
		}
	}

	/**
	 * Deletes an existing Techs model.
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
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Techs model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Techs the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Techs::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
	
	/**
	 * Returns tech available network ports
	 * @return array
	 */
	public function actionPortList()
	{
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		if (isset($_POST['depdrop_parents'])) {
			$parents = $_POST['depdrop_parents'];
			if ($parents != null) {
				$model=$this->findModel($parents[0]);
				//$out = self::getSubCatList($cat_id);
				// the getSubCatList function will query the database based on the
				// cat_id and return an array like below:
				// [
				//    ['id'=>'<sub-cat-id-1>', 'name'=>'<sub-cat-name1>'],
				//    ['id'=>'<sub-cat_id_2>', 'name'=>'<sub-cat-name2>']
				// ]
				return ['output'=>$model->ddPortsList, 'selected'=>''];
			}
		}
		return ['output'=>'', 'selected'=>''];
	}
	
	/**
	 * Обновляем элемент оборудования
	 * @param $id
	 * @return string|\yii\web\Response
	 * @throws NotFoundHttpException
	 */
	public function actionUpdhw($id){
		
		$model = $this->findModel($id);
		
		//проверяем передан ли uid
		$uid=Yii::$app->request->get('uid',null);
		if (strlen($uid)) {
			if ($uid==='sign-all') { //специальная команда на подпись всего оборудования
				//error_log('signing all');
				$model->hwList->signAll();
			}else {
				$newItem = new \app\models\HwListItem();
				$newItem->loadArr($_GET);
				$model->hwList->add($newItem);
			}
			//error_log('saving');
			//сохраняем без проверки валидности, т.к. пользователь не может изменить данные
			if (!$model->save(false)) error_log(print_r($model->errors,true));
		}
		
		return $this->redirect(['passport', 'id' => $model->id]);
	}
	
	/**
	 * Обновляем элемент оборудования
	 * @param $id
	 * @return string|\yii\web\Response
	 * @throws NotFoundHttpException
	 */
	public function actionEdithw($id){
		
		$manufacturers=\app\models\Manufacturers::fetchNames();
		$model = $this->findModel($id);
		
		//проверяем передан ли uid
		$uid=Yii::$app->request->get('uid',null);
		$editItem=null;
		foreach ($model->hwList->items as $pos=>$item) {
			if ($item->uid == $uid) $editItem=$item;
		}
		if (!$editItem) $editItem = new \app\models\HwListItem();
		
		return Yii::$app->request->isAjax?
		$this->renderAjax( '/hwlist/edit-item',
			[
				'item'=>$editItem,
				'model'=>$model,
				'manufacturers'=>$manufacturers,
				'modalParent' => '#modal_form_loader'
			]):
		$this->render( '/hwlist/edit-item',
			[
				'item'=>$editItem,
				'model'=>$model,
				'manufacturers'=>$manufacturers,
			]);
	}
	
	/**
	 * Удаляем элемент оборудования
	 * @param $id
	 * @return string|\yii\web\Response
	 * @throws NotFoundHttpException
	 */
	public function actionRmhw($id){
		
		$model = $this->findModel($id);
		
		//проверяем передан ли uid
		if (strlen(Yii::$app->request->get('uid',null))) {
			$model->hwList->del(Yii::$app->request->get('uid'));
			//сохраняем без проверки валидности, т.к. пользователь не может изменить данные
			$model->save(false);
		}
		
		return $this->redirect(['passport', 'id' => $model->id]);
	}
}
