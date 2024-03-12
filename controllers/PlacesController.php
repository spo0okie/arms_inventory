<?php

namespace app\controllers;

use app\models\ManufacturersDict;
use app\models\Places;
use app\models\ui\MapItemForm;
use Throwable;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;

/**
 * PlacesController implements the CRUD actions for Places model.
 */
class PlacesController extends ArmsBaseController
{
	public $modelClass='\app\models\Places';
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['armmap','depmap'],
			'edit'=>['uploads','map-set','map-delete'],
		]);
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
					'materials.children',
					'materials.usages', //дико добавляет тормозов
				])->orderBy('short')
				->all(),
			'show_archived'=> Yii::$app->request->get('showArchived',false),
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
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
			'show_archived'=> Yii::$app->request->get('showArchived',false),
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
					'materials.children',
					'materials.usages', //дико добавляет тормозов
				])->orderBy('short')
		        ->all(),
        ]);
    }
	
	
	/**
	 * Deletes an existing Places model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id)
    {
    	/** @var Places $model */
    	$model=$this->findModel($id);
    	$parent_id=$model->parent_id;
        $model->delete();
		if (is_null($parent_id))
            return $this->redirect(['index']);
		else
			return $this->redirect(['view','id'=>$parent_id]);
    }
	
	/**
	 * Updates an existing TechModels model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUploads(int $id)
	{
		$model = $this->findModel($id);
		return $this->render('uploads', [
			'model' => $model,
		]);
	}
	
	public function actionMapSet(int $id){
		$model=new MapItemForm();
		$model->load(Yii::$app->request->post());
		$model->itemSet();
		return $this->redirect(['view','id'=>$model->place_id]);
		
	}

	public function actionMapDelete(int $id, string $item_type, int $item_id){
		/** @var Places $model */
		$model=$this->findModel($id);
		$mapStruct=json_decode($model->map);
		
		if (property_exists($mapStruct,$item_type)) {
			$items=$mapStruct->$item_type;
			if (property_exists($items,$item_id)) {
				unset ($items->$item_id);
				$mapStruct->$item_type=$items;
				$model->map=json_encode($mapStruct,JSON_UNESCAPED_UNICODE);
				$model->save();
			}
		}
		
		return $this->redirect(['view','id'=>$id]);
	}
}
