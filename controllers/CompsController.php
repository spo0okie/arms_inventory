<?php

namespace app\controllers;

use app\models\Domains;
use Yii;
use app\models\Comps;
use app\models\CompsSearch;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * CompsController implements the CRUD actions for Comps model.
 */
class CompsController extends ArmsBaseController
{
	public $modelClass='app\models\Comps';
	
    /**
     * @inheritdoc
     */
    public function accessMap()
    {
		return array_merge_recursive(
			parent::accessMap(),
			[
				'edit'=>['unlink','addsw','rmsw','ignoreip','unignoreip','dupes','absorb'],
				'view'=>['ttip-hw','item','item-by-name'],
			]);
		
    }

    /**
     * Lists all Comps models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CompsSearch();
        $searchModel->archived=\Yii::$app->request->get('showArchived',false);
	
		//ищем тоже самое но с дочерними в противоположном положении
		$switchArchived=clone $searchModel;
		$switchArchived->archived=!$switchArchived->archived;
		$switchArchivedCount=$switchArchived->search(Yii::$app->request->queryParams)->totalCount;
	
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
			'switchArchivedCount' => $switchArchivedCount,
        ]);
    }
	
    /**
     * Lists all Comps models.
     * @return mixed
     */
    public function actionDupes()
    {
		$dupes = (new Query())
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
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionItem(int $id)
	{
		return $this->renderPartial('item', [
			'model' => $this->findModel($id)
		]);
	}
	
	public function actionItemByName($name)
	{
		$nameParts=Domains::fetchFromCompName($name);
		
		if ($nameParts===false) {
			throw new BadRequestHttpException('Invalid comp name format');
		}
		
		$domain_id=$nameParts[0];
		$compName=$nameParts[1];
		$domainName=$nameParts[2];
		
		if (is_null($domain_id)) {
			throw new NotFoundHttpException("Domain $domainName not found");
		} elseif ($domain_id===false) {
			if (is_null($model = Comps::findOne(['name'=>$compName]))) {
				throw new NotFoundHttpException("Computer $compName not found");
			}
		} elseif (is_null($model = Comps::findOne(['name'=>$compName,'domain_id'=>$domain_id]))) {
			throw new NotFoundHttpException("Computer $compName not found in domain $domainName");
		}
		
		return $this->renderPartial('item', ['model' => $model	,'static_view'=>true]);
	}
	
	
	
	/**
	 * Absorb other comp in this
	 * @param int $id
	 * @param $absorb_id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionAbsorb(int $id, $absorb_id)
	{
		$model = $this->findModel($id);
		/** @var Comps $model */
		$absorb = $this->findModel($absorb_id);
		/** @var Comps $absorb */
		
		$model->absorbComp($absorb);
		return $this->redirect(['view', 'id' => $model->id]);
	}
	
	/**
	 * Displays a tooltip for hw of single model.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionTtipHw(int $id)
	{
		return $this->renderPartial('/techs/ttip-hw', [
			'model' => $this->findModel($id),
		]);
	}
	

	/**
	 * Найти ОС по имени DOMAIN\computer, computer.domain.local или отдельно передав домен
	 * @param string      $name
	 * @param null|string $domain
	 * @param null|string $ip
	 * @return array|ActiveRecord
	 * @throws NotFoundHttpException|BadRequestHttpException
	 */
	public static function searchModel(string $name, $domain=null, $ip=null){
		
		$name=strtoupper($name);

		if ($domain) {
			$domain_id=Domains::findByAnyName($domain);
			$compName=$name;
			$domainName=$domain;
		} else {
			$nameParse=Domains::fetchFromCompName($name);
			if (!is_array($nameParse)) throw new BadRequestHttpException("Incorrect comp name $name");
			
			[$domain_id,$compName,$domainName]=$nameParse;
		}

		if (is_null($domain_id)) throw new \yii\web\NotFoundHttpException("Domain $domainName not found");

		$notFoundDescription="Comp with name '$compName'";
		$query=\app\models\Comps::find()->where(['LOWER(name)'=>mb_strtolower($compName)]);
		
		//добавляем фильтрацию по IP если он есть
		if (!is_null($ip)) {
			//если передано несколько адресов (через пробел)
			$query->andFilterWhere(['or like','ip',explode(' ',trim($ip))]);
			$notFoundDescription.=" with IP $ip";
		}
		
		$notFoundDescription.=" not found";
		
		//добавляем фильтрацию по домену если он есть
		if ($domain_id!==false) {
			//добавляем домен к условию поиска
			$query->andFilterWhere(['domain_id'=>$domain_id]);
			$notFoundDescription.=" in domain $domainName";
		}
		
		$model = $query->one();
		
		if ($model === null)
			throw new \yii\web\NotFoundHttpException($notFoundDescription);
		
		return $model;
	}

	/**
	 * Обновляем элементы ПО
	 * @param $id
	 * @return string|Response
	 * @throws NotFoundHttpException
	 */
	public function actionAddsw($id){
		//if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
		
		$model = $this->findModel($id);
		/** @var Comps $model */

		//проверяем передан ли a
		$strItems=Yii::$app->request->get('items',null);
		if (strlen($strItems)) {
			if ($strItems==='sign-all') {
				$items=array_keys($model->swList->getAgreed());
			} else
				($items=explode(',',$strItems));

			if (is_array($items)) {
				$model->soft_ids=array_unique(array_merge($model->soft_ids,$items));
				$model->silentSave();
			}
		}

		return $this->redirect(['/techs/passport', 'id' => $model->arm_id]);
	}

    /**
     * Удаляем элементы ПО
     * @param $id
     * @return string|Response
     * @throws NotFoundHttpException
     */
    public function actionRmsw($id){
	    //if (!\app\models\Users::isAdmin()) {throw new  \yii\web\ForbiddenHttpException('Access denied');}
	
	    $model = $this->findModel($id);
		/** @var Comps $model */

        //проверяем передан ли a
        $strItems=Yii::$app->request->get('items',null);
        if (strlen($strItems)) {
            if (is_array($items=explode(',',$strItems))){
                $model->soft_ids=array_diff($model->soft_ids,$items);
                $model->silentSave();
            }
		}

        return $this->redirect(['/techs/passport', 'id' => $model->arm_id]);
    }
	/**
	 * Обновляем  список скрытых IP
	 * @param $id
	 * @param $ip
	 * @return string|Response
	 * @throws NotFoundHttpException
	 */
	public function actionIgnoreip($id,$ip){
		$model = $this->findModel($id);
		/** @var Comps $model */

		$ignored=explode("\n",$model->ip_ignore);
		$ignored[]=$ip;
		$model->ip_ignore=implode("\n",array_unique($ignored));
		$model->save();

		return $this->redirect(['/comps/view', 'id' => $model->id]);
	}

	/**
	 * Обновляем  список скрытых IP
	 * @param $id
	 * @param $ip
	 * @return string|Response
	 * @throws NotFoundHttpException
	 */
	public function actionUnignoreip($id,$ip){
		$model = $this->findModel($id);
		/** @var Comps $model */

		$ignored=explode("\n",$model->ip_ignore);
		$id=array_search($ip,$ignored);
		if (!is_null($id)) {
			unset($ignored[$id]);
			$model->ip_ignore=implode("\n",array_unique($ignored));
			$model->save();
		}
		
		return $this->redirect(['/comps/view', 'id' => $model->id]);
	}

}
