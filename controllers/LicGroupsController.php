<?php

namespace app\controllers;

use app\models\LicItemsSearch;
use app\models\links\LicLinks;
use Yii;
use app\models\LicGroups;
use yii\data\ArrayDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * LicGroupsController implements the CRUD actions for LicGroups model.
 */
class LicGroupsController extends ArmsBaseController
{
	public $modelClass=LicGroups::class;

    /**
     * Displays a single LicGroups model.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id)
    {
	    $searchModel = new LicItemsSearch();
	    $query=Yii::$app->request->queryParams;
	    if (!isset($query['LicItemsSearch'])) $query['LicItemsSearch']=[];
	    $query['LicItemsSearch']['lic_group_id']=$id;
	    $dataProvider = $searchModel->search($query);
	    
	    $linksData=new ArrayDataProvider([
			'allModels' => LicLinks::findForLic('groups',$id),
			'key'=>'id',
			'sort' => [
				'attributes'=> [
					'objName',
					'comment',
					'changedAt',
					'changedBy',
				],
				'defaultOrder' => [
					'objName' => SORT_ASC
				]
			],
			'pagination' => false,
		]);
			

        return $this->render('view', [
            'model' => $this->findModel($id),
		    'searchModel' => $searchModel,
		    'dataProvider' => $dataProvider,
	        'linksData' => $linksData,
	    ]);

    }
	
	
	/**
	 * Удаляем АРМ или софт из лицензии
	 * @param int $id
	 * @param int|null $soft_id
	 * @param int|null $arms_id
	 * @param int|null $users_id
	 * @param int|null $comps_id
	 * @return string|Response
	 * @throws NotFoundHttpException
	 */
	public function actionUnlink(int $id, $soft_id=null, $arms_id=null, $users_id=null, $comps_id=null){
		/** @var LicGroups $model */
		$model = $this->findModel($id);
		$updated = false;

		//если нужно отстегиваем софт
		if (!is_null($soft_id)) {
			$model->soft_ids=array_diff($model->soft_ids,[$soft_id]);
			$updated=true;
		}

		//если нужно то АРМ
		if (!is_null($arms_id)) {
			$model->arms_ids=array_diff($model->arms_ids,[$arms_id]);
			$updated=true;
		}
		
		//если нужно то Комп
		if (!is_null($comps_id)) {
			$model->comps_ids=array_diff($model->comps_ids,[$comps_id]);
			$updated=true;
		}
		
		//если нужно то Пользователя
		if (!is_null($users_id)) {
			$model->users_ids=array_diff($model->users_ids,[$users_id]);
			$updated=true;
		}

		//сохраняем
		if ($updated) $model->save();

		return $this->redirect(['view', 'id' => $model->id]);
	}

}
