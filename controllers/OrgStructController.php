<?php

namespace app\controllers;

use app\helpers\ArrayHelper;
use app\models\UsersSearch;
use Yii;
use app\models\OrgStruct;
use yii\web\NotFoundHttpException;
use yii\helpers\Url;
use yii\web\Response;

/**
 * OrgStructController implements the CRUD actions for OrgStruct model.
 */
class OrgStructController extends ArmsBaseController
{
	public $modelClass=OrgStruct::class;

	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['dep-drop']
		]);
	}
	
	/**
	 * Lists all OrgStruct models.
	 * @param int $org_id Организация, чье расписание показываем
	 * @return mixed
	 */
    public function actionIndex($org_id=1)
    {
		return $this->render('index', [
			'models' => OrgStruct::find()
				->where(['org_id'=>$org_id,'parent_id'=>null])
				->orderBy(['name'=>SORT_ASC])
				->all(),
			'org_id'=>$org_id
		]);
    }
	
	
	/**
	 * Displays a single OrgStruct model.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
    public function actionView(int $id)
    {
    	/** @var OrgStruct $model */
		$model=$this->findModel($id);
  
		$params=Yii::$app->request->queryParams;
		$params=ArrayHelper::setTreeDefaultValue($params,['UsersSearch','org_id'],$model->org_id);
		$params=ArrayHelper::setTreeDefaultValue($params,['UsersSearch','Orgeh'],$model->hr_id);
  
		$searchModel = new UsersSearch();
		$dataProvider = $searchModel->search($params);
		
        return $this->render('view', [
            'model' => $model,
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new OrgStruct model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new OrgStruct();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->get('return')=='previous') return $this->redirect(Url::previous());
            return $this->redirect(['view', 'id' => $model->id, 'org_id' => $model->org_id]);
        }
	
		$model->load(Yii::$app->request->get());
        return $this->render('create', [
            'model' => $model,
        ]);
    }
    
	
	/**
	 * Возвращает подразделения организации
	 * Бэкенд для Dependant Dropdown (меняем орг-ю, меняются подразделения)
	 * @return array
	 */
	public function actionDepDrop()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;
		if (isset($_POST['depdrop_parents'])) {
			$parents = $_POST['depdrop_parents'];
			if ($parents != null) {
				$models=OrgStruct::fetchOrgNames($parents);
				$output=[];
				foreach ($models as $id=>$name) {
					$output[]=['id'=>$id,'name'=>$name];
				}
				//$out = self::getSubCatList($cat_id);
				// the getSubCatList function will query the database based on the
				// cat_id and return an array like below:
				// [
				//    ['id'=>'<sub-cat-id-1>', 'name'=>'<sub-cat-name1>'],
				//    ['id'=>'<sub-cat_id_2>', 'name'=>'<sub-cat-name2>']
				// ]
				return ['output'=>$output, 'selected'=>''];
			}
		}
		return ['output'=>'', 'selected'=>''];
	}
	
}
