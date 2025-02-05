<?php

namespace app\controllers;

use app\models\HwListItem;
use app\models\ManufacturersDict;
use Yii;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * ArmsController implements the CRUD actions for Arms model.
 */
class ArmsController extends ArmsBaseController
{
	public $modelClass='app\models\Techs';
	
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
			'class' => AccessControl::className(),
			'rules' => [
				['allow' => true, 'actions'=>['create','create-apply','update','update-apply','delete','unlink','updhw','rmhw'], 'roles'=>['editor']],
				['allow' => true, 'actions'=>['index','view','ttip','ttip-hw','validate','contracts'], 'roles'=>['@','?']],
			],
			'denyCallback' => function ($rule, $action) {
				throw new  ForbiddenHttpException('Access denied');
			}
		];
		return $behaviors;
    }
    
    public function actionIndex()
	{
		$this->setQueryParam(['TechsSearch'=>['is_computer'=>true]]);
		ManufacturersDict::initCache();
		return parent::actionIndex();
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
     * Обновляем элемент оборудования
     * @param $id
     * @return string|Response
     * @throws NotFoundHttpException
     */
    public function actionUpdhw($id){
	    //if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}

        $model = $this->findModel($id);

        //проверяем передан ли uid
        $uid=Yii::$app->request->get('uid',null);
        if (strlen($uid)) {
            if ($uid==='sign-all') { //специальная команда на подпись всего оборудования
	            //error_log('signing all');
                $model->hwList->signAll();
            }else {
                $newItem = new HwListItem();
                $newItem->loadArr($_GET);
                $model->hwList->add($newItem);
            }
            //error_log('saving');
			//сохраняем без проверки валидности, т.к. пользователь не может изменить данные
            if (!$model->save(false)) error_log(print_r($model->errors,true));
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Удаляем элемент оборудования
     * @param $id
     * @return string|Response
     * @throws NotFoundHttpException
     */
    public function actionRmhw($id){
	    //if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}

        $model = $this->findModel($id);

        //проверяем передан ли uid
        if (strlen(Yii::$app->request->get('uid',null))) {
            $model->hwList->del(Yii::$app->request->get('uid'));
			//сохраняем без проверки валидности, т.к. пользователь не может изменить данные
			$model->save(false);
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }
}
