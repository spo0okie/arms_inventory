<?php

namespace app\controllers;

use app\models\Departments;
use app\models\ManufacturersDict;
use Codeception\Module\Yii2;
use Yii;
use app\models\Places;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PlacesController implements the CRUD actions for Places model.
 */
class PlacesController extends ArmsBaseController
{
	public $modelClass='\app\models\Places';
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
			    ['allow' => true, 'actions'=>['create','update','delete','unlink'], 'roles'=>['editor']],
			    ['allow' => true, 'actions'=>['index','view','ttip','validate','armmap','depmap'], 'roles'=>['@','?']],
		    ],
		    'denyCallback' => function ($rule, $action) {
			    throw new  \yii\web\ForbiddenHttpException('Access denied');
		    }
	    ];
	    return $behaviors;
    }
    

    /**
     * Lists all Places models.
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index', [
            'models' => Places::find()
	            ->select([
	            	'{{places}}.*',
		            'getplacepath(id) AS path'
	            ])
	            ->orderBy('path')->all(),
        ]);
    }
	
	/**
	 * Lists all Places models.
	 * @return mixed
	 */
	public function actionArmmap()
	{
		ManufacturersDict::initCache();
		return $this->render('armmap', [
			'models' => Places::find()
				->joinWith([
					'phones',
					'inets',
					'techs.comp.domain',
					'techs.comps.services',
					'techs.licKeys',
					'techs.licItems',
					'techs.licGroups',
					'techs',
					'techs.contracts',
					'techs.state',
					'techs.model.type',
					'techs.model.manufacturer',
					'techs.user',
					'materials.type',
					'materials.childs',
					'materials.usages', //дико добавляет тормозов
				])->orderBy('short')
				->all(),
			'show_archived'=>\Yii::$app->request->get('showArchived',false),
		]);
	}
	/**
	 * Lists all Places models.
	 * @return mixed
	 */
	public function actionDepmap()
	{
		
		$dataProvider = new ActiveDataProvider([
			'query' => Places::find()
				->joinWith(['techs'])
				->where(['not',['places_techs.departments_id'=>null]])
				->groupBy('getplacetop(places.id)'),
				//->all()
			'pagination'=>false
		]);
		
		return $this->render('depmap', [
			'dataProvider' => $dataProvider,
		]);
	}
	
	
	/**
     * Displays a single Places model.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
	        'models' => Places::find()
				->joinWith([
					'phones',
					'inets',
					'techs.comp.domain',
					'techs.comps.services',
					'techs.licKeys',
					'techs.licItems',
					'techs.licGroups',
					'techs',
					'techs.contracts',
					'techs.state',
					'techs.model.type',
					'techs.model.manufacturer',
					'techs.user',
					'materials.type',
					'materials.childs',
					'materials.usages', //дико добавляет тормозов
				])->orderBy('short')
		        ->all(),
        ]);
    }



    /**
     * Deletes an existing Places model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
    	$model=$this->findModel($id);
    	$parent_id=$model->parent_id;
        $model->delete();
		if (is_null($parent_id))
            return $this->redirect(['index']);
		else
			return $this->redirect(['view','id'=>$parent_id]);
    }

    /**
     * Finds the Places model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Places the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Places::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
