<?php

namespace app\controllers;

use app\helpers\ArrayHelper;
use app\models\Contracts;
use app\models\UsersSearch;
use Yii;
use app\models\Partners;
use yii\web\NotFoundHttpException;

/**
 * PartnersController implements the CRUD actions for Partners model.
 */
class PartnersController extends ArmsBaseController
{
	public $modelClass=Partners::class;
	
    /**
     * Displays a single Partners model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id)
    {
    	$contracts=Contracts::find()
			->joinWith([
				'currency',
				'techs',
				'licItems',
				'services',
				
			])
			->join('LEFT JOIN','partners_in_contracts','`partners_in_contracts`.`contracts_id`=`contracts`.`id`')
			->where(['partners_in_contracts.partners_id'=>$id])
			->orderBy(['date'=>SORT_DESC])
			->all();
	
		$params=Yii::$app->request->queryParams;
		$params=ArrayHelper::setTreeDefaultValue($params,['UsersSearch','org_id'],$id);
	
	
		$searchModel = new UsersSearch();
		$dataProvider = $searchModel->search($params);
	
		return $this->render('view', [
            'model' => $this->findModel($id),
			'contracts'=>$contracts,
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
        ]);
    }


}
