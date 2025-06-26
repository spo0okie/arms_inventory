<?php

namespace app\modules\api\controllers;

use app\models\Comps;
use app\models\CompsSearch;
use Yii;
use yii\web\BadRequestHttpException;


class CompsController extends BaseRestController
{
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'update-comps'=>['push']
		]);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		$behaviors=parent::behaviors();
		$behaviors['verbFilter']['actions']['push']=['POST'];
		$behaviors['verbFilter']['actions']['update']=['POST','PUT','PATCH'];
		return $behaviors;
	}
	
	public $modelClass='app\models\Comps';
	
	public static $searchFields=[
		'name'=>'name',
		'ip'=>'ip',
		'mac'=>'mac',
	];
	
	public function actionSearch($name=null,$domain=null,$ip=null) {
		if ($name) return \app\controllers\CompsController::searchModel($name,$domain,$ip);
		return parent::actionSearch();
	}
	
	public function actionFilter(){
		$searchModel = new CompsSearch();
		$searchModel->archived= Yii::$app->request->get('showArchived',false);
		return $searchModel->search(Yii::$app->request->queryParams)->models;
    }
    
    public function actionPush() {
    	/** @var Comps $loader */
		$loader = new $this->modelClass();
	
		//грузим переданные данные
		if (!$loader->load(Yii::$app->getRequest()->getBodyParams(),'')) {
			throw new BadRequestHttpException("Error loading posted data");
		}
		
		//передали ID?
		if ($loader->id) {
			return $this->runAction('update',['id'=>$loader->id]);
		}
		
		$search=Comps::findByAnyName($loader->name,'workgroup');
		if (is_object($search)&&$search->id) {
			return $this->runAction('update',['id'=>$search->id]);
		}
	
		return $this->runAction('create');
	}
}
