<?php

namespace app\controllers;

use Yii;
use app\models\Comps;
use app\models\CompsSearch;
use yii\data\ActiveDataProvider;
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
				['allow' => true, 'actions'=>['create','update','delete','unlink','addsw','rmsw','ignoreip','unignoreip','dupes','absorb'], 'roles'=>['editor']],
				['allow' => true, 'actions'=>['index','view','ttip','ttip-hw','item','item-by-name'], 'roles'=>['@','?']],
			],
			'denyCallback' => function ($rule, $action) {
				throw new  \yii\web\ForbiddenHttpException('Access denied');
			}
		];
		return $behaviors;
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
     * Lists all Comps models.
     * @return mixed
     */
    public function actionDupes()
    {
		$dupes = (new \yii\db\Query())
			->select(['GROUP_CONCAT(id) ids','name','COUNT(*) c'])
			->from('comps')
			->groupBy(['name'])
			->having('c > 1')
			->all();
		$ids=[];
		foreach ($dupes as $item) $ids=array_merge($ids , explode(',',$item['ids']));
	
		// add conditions that should always apply here
		
		$query=Comps::find()->where(['id'=>$ids]);
		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'pagination' => ['pageSize' => 100,],
		]);
	
        return $this->render('index', [
            'dataProvider' => $dataProvider,
			'searchModel' => null
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
	
	public function actionItemByName($name)
	{
		//распарсиваем FQDN
		if (strpos($name,'.')>0) {
			$tokens=explode('.',$name);
			$compName=$tokens[0];
			unset ($tokens[0]);
			$fqdn=implode('.',$tokens);
			if (($domain = \app\models\Domains::findOne(['fqdn'=>$fqdn])) !== null) {
				if (($model = Comps::findOne(['name'=>$compName,'domain_id'=>$domain->id])) !== null) {
					return $this->renderPartial('item', ['model' => $model,'static_view'=>true]);
				}
			}
			throw new NotFoundHttpException('The requested page does not exist.');
		}
		
		//иначе в формате домен/имя
		$tokens=explode('\\',$name);
		if (count($tokens)==1) {
			if (($model = Comps::findOne(['name'=>$name])) !== null) {
				return $this->renderPartial('item', ['model' => $model	,'static_view'=>true]);
			}
			throw new NotFoundHttpException('The requested page does not exist.');
		} elseif (count($tokens)==2) {
			if (($domain = \app\models\Domains::findOne(['name'=>$tokens[0]])) !== null) {
				if (($model = Comps::findOne(['name'=>$tokens[1],'domain_id'=>$domain->id])) !== null) {
					return $this->renderPartial('item', ['model' => $model	,'static_view'=>true]);
				}
			}
			throw new NotFoundHttpException('The requested page does not exist.');
		}
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
	 * Absorb other comp in this
	 * @param integer $id
	 * @param         $absorb_id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionAbsorb($id, $absorb_id)
	{
		$model = $this->findModel($id);
		$absorb = $this->findModel($absorb_id);
		
		$model->absorbComp($absorb);
		return $this->redirect(['view', 'id' => $model->id]);
	}
	
	/**
	 * Displays a tooltip for hw of single model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionTtipHw($id)
	{
		return $this->renderPartial('/arms/ttip-hw', [
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
	 * Найти ОС по имени DOMAIN\computer, computer.domain.local или отдельно передав домен
	 * @param string      $name
	 * @param null|string $domain
	 * @param null|string $ip
	 * @return array|\yii\db\ActiveRecord
	 * @throws NotFoundHttpException
	 */
	public static function searchModel(string $name, $domain=null, $ip=null){
		$notFoundDescription="Comp with name '$name'";
		
		$name=strtoupper($name);
		
		$domainObj=null;
		if (strpos($name,'.')!==false) {
			//fqdn passed
			$tokens=explode('.',$name);
			$name=$tokens[0];
			unset($tokens[0]);
			$domain=implode('.',$tokens);
			
			//ищем домен
			if (is_null($domainObj=\app\models\Domains::find()
				->where(['fqdn'=>$domain])
				->one()
			)) throw new \yii\web\NotFoundHttpException("Domain with fqdn='$domain' not found");
			
		} else { //no FQDN
			if (is_null($domain) && strpos($name,'\\')!==false) {
				//DOMAIN\computer notation
				$tokens=explode('\\',$name);
				$domain=$tokens[0];
				$name=$tokens[1];
			}
			if ($domain && is_null($domainObj=\app\models\Domains::find()
				->where(['name'=>strtoupper($domain)])
				->one()
			)) throw new \yii\web\NotFoundHttpException("Domain '$domain' not found");
		}
		
		
		$query=\app\models\Comps::find()->where(['name'=>strtoupper($name)]);
		
		//добавляем фильтрацию по IP если он есть
		if (!is_null($ip)) {
			//если передано несколько адресов (через пробел)
			$query->andFilterWhere(['or like','ip',explode(' ',trim($ip))]);
			$notFoundDescription.=" with IP $ip";
		}
		
		$notFoundDescription.=" not found";
		
		//добавляем фильтрацию по домену если он есть
		if (is_object($domainObj)) {
			//добавляем домен к условию поиска
			$query->andFilterWhere(['domain_id'=>$domainObj->id]);
			$notFoundDescription.=" in domain $domain";
		}
		
		$model = $query->one();
		
		if ($model === null)
			throw new \yii\web\NotFoundHttpException($notFoundDescription);
		
		return $model;
	}

	/**
	 * Обновляем элементы ПО
	 * @param $id
	 * @return string|\yii\web\Response
	 * @throws NotFoundHttpException
	 */
	public function actionAddsw($id){
		//if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
		
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
	    //if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
	
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
		//if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
		
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
		//if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
		
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
