<?php

namespace app\controllers;

use app\components\llm\LlmClient;
use app\components\RackWidget;
use app\helpers\FieldsHelper;
use app\models\Manufacturers;
use app\models\ManufacturersDict;
use app\models\TechsSearch;
use Throwable;
use Yii;
use app\models\TechModels;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * TechModelsController implements the CRUD actions for TechModels model.
 */
class TechModelsController extends ArmsBaseController
{
	
	public $modelClass='app\models\TechModels';
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['hint-comment','hint-template','hint-description',],
			'edit'=>['uploads','render-rack']
		]);
	}
	
	
	
	/**
     * {@inheritdoc}
     */
    public function behaviors()
    {
    	return array_merge_recursive(parent::behaviors(),[
			'verbs' => [
				'actions' => [
					'render-rack' => ['POST'],
				],
			]
		]);
    }
	
	
	/**
	 * Displays a item for single model.
	 * @param int  $id
	 * @param null $long
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionItem(int $id, $long=null)
	{
		return $this->renderPartial('item', [
			'model'	=> $this->findModel($id),
			'long'	=> $long,
		]);
	}
	
	
	public function actionItemByName($name)
	{
		$manufacturer=Yii::$app->request->get('manufacturer');
		$long=Yii::$app->request->get('long');
		/// производитель
		//ищем в словаре
		if (is_null($man_id= ManufacturersDict::fetchManufacturer($manufacturer))) {
			//ищем в самих производителях
			if (!is_object($man_obj = Manufacturers::findOne(['name'=>$manufacturer]))) {
				throw new NotFoundHttpException('Requested manufacturer not found');
			} else {
				$man_id=$man_obj->id;
			}
		}
		
		if (($model = TechModels::findOne(['short'=>$name,'manufacturers_id'=>$man_id])) !== null) {
			return $this->renderPartial('item', [
				'model' => $model,
				'long'	=> $long,
			]);
		}

		if (($model = TechModels::findOne(['name'=>$name,'manufacturers_id'=>$man_id])) !== null) {
			return $this->renderPartial('item', [
				'model' => $model,
				'long'	=> $long,
			]);
		}

		throw new NotFoundHttpException('The requested model not found within that manufacturer');
	}
	
	
	
	/**
	 * Подсказка по заполнению спеки (берется из типа модели)
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionHintTemplate(int $id)
	{
		/** @var TechModels $model */
		$model=$this->findModel($id);
		if ($model->individual_specs)
			return Yii::$app->formatter->asNtext($model->type->comment);
		else
			return \app\models\TechModels::$no_specs_hint;
	}
	
	/**
	 * Информация о модели
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionHintDescription(int $id)
	{
		$model=$this->findModel($id);
		return Yii::$app->formatter->asNtext($model->comment);
	}
	
	
	
	public function actionHintComment(int $id){
		Yii::$app->response->format = Response::FORMAT_JSON;
		$data=\app\models\TechModels::fetchTypeComment($id);
		if (!is_array($data)) throw new NotFoundHttpException('The requested data does not exist.');
		//переоформляем под qtip
		$data['hint']=FieldsHelper::toolTipOptions($data['name'],$data['hint'])['qtip_ttip'];
		return $data;
	}
	
	
	/**
	 * Displays a single TechModels model.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionView(int $id)
	{
		$this->setQueryParam(['TechsSearch'=>['model_id'=>$id]]);
		
		$techSearchModel = new TechsSearch();
		$techDataProvider = $techSearchModel->search(Yii::$app->request->queryParams);
		
		return $this->render('view', [
			'model' => $this->findModel($id),
			'searchModel' => $techSearchModel,
			'dataProvider' => $techDataProvider,
		]);
	}
	
	/**
	 * Displays a single TechModels model.
	 * @return mixed
	 * @throws Throwable
	 */
	public function actionRenderRack()
	{
		return RackWidget::widget(
			json_decode(
				Yii::$app->request->getBodyParam('config'),true
			)
		);
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
	
	
	public function actionGenerateDescription()
	{
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		
		$name = Yii::$app->request->post('name');
		$manufacturer = Yii::$app->request->post('manufacturer');
		$type = Yii::$app->request->post('type');
		
		if (!$name) {
			return ['error' => 'Не указана модель'];
		}
		
		if (!$manufacturer) {
			return ['error' => 'Не указан производитель'];
		}
		
		if (!$type) {
			return ['error' => 'Не указан тип оборудования'];
		}
		
		$vendor=\app\models\Manufacturers::findOne($manufacturer);
		$techType=\app\models\TechTypes::findOne($type);
		$generator = new LlmClient();
		$result = $generator->generateTechModelDescription($techType->name, $vendor->name.' '.$name,$techType->comment);
		
		if (!$result) {
			return ['error' => 'Не удалось получить описание'];
		}
		
		return ['success' => true, 'data' => $result];
	}
	
}
