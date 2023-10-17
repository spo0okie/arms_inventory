<?php

namespace app\controllers;

use Yii;
use app\models\OrgPhones;
use yii\data\ActiveDataProvider;

/**
 * OrgPhonesController implements the CRUD actions for OrgPhones model.
 */
class OrgPhonesController extends ArmsBaseController
{
	
	public $modelClass=OrgPhones::class;

    /**
     * Lists all OrgPhones models.
     * @return mixed
     */
    public function actionIndex()
    {
    	$query=OrgPhones::find();
    	if (!Yii::$app->request->get('showArchived',false))
    		$query->where(['not',['archived'=>1]]);

    	
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }
}
