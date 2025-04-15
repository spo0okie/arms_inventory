<?php

namespace app\controllers;

use app\models\Places;
use app\models\Soft;
use app\models\TechModels;
use app\models\Techs;
use Throwable;
use Yii;
use app\models\Scans;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\web\Response;
use yii\bootstrap5\ActiveForm;

/**
 * ScansController implements the CRUD actions for Scans model.
 */
class ScansController extends ArmsBaseController
{
	public $modelClass=Scans::class;
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'edit'=>['thumb'],
		]);
	}
	


	/**
	 * Validates Manufacturers model on update.
	 * @param null $id
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionValidate($id=null)
	{
		if (!is_null($id))
			$model = $this->findModel($id);
		else
			$model = new Scans();

		if ($model->load(Yii::$app->request->post())) {
			$model->scanFile = UploadedFile::getInstance($model, 'scanFile');
			Yii::$app->response->format = Response::FORMAT_JSON;
			return ActiveForm::validate($model);
		}
		
		return null;
	}

    /**
     * Creates a new Scans model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Scans();

	    if ($model->load(Yii::$app->request->post())) {
		    $model->scanFile = UploadedFile::getInstance($model, 'scanFile');
		    if (!$model->validate()) {
		    	$errors=[];
			    foreach ($model->getErrors() as $attribute => $errors) {
				    $errors[yii\helpers\Html::getInputId($model, $attribute)] = $errors;
			    }
			    Yii::$app->response->format = Response::FORMAT_JSON;
			    return (object)[
			    	'error'=>'не прошло валидацию',
				    'validation'=>$errors
			    ];

		    }
		    if (!$model->upload()) return "{\"error\":\"не удалось загрузить\"}";
		    if ($model->save(false)) {
			    Yii::$app->response->format = Response::FORMAT_JSON;
			    return [$model];
		    }
		    return '{"error":"ошибка сохранения модели"}';
	    }
	    return '{"error":"ошибка получения данных"}';

    }
	
	/**
	 * Updates an existing Scans model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdate(int $id)
	{
		/** @var Scans $model */
		$model = $this->findModel($id);
		
		if ($model->load(Yii::$app->request->post())) {
			$model->scanFile = UploadedFile::getInstance($model, 'scanFile');
			if ($model->upload()&&$model->save())
				return $this->redirect(['view', 'id' => $model->id]);
		}
		
		return $this->render('update', [
			'model' => $model,
		]);
	}
	
	/**
	 * Устанавливает переданный по ID скан превьюшкой указанного объекта
	 * @param integer $id	ID скана
	 * @param string  $link	тип объекта
	 * @param integer $link_id ID объекта
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionThumb(int $id, string $link, int $link_id)
	{
		switch ($link) {
			case 'tech_models_id':
				$model = TechModels::findOne($link_id);
				break;
			case 'techs_id':
				$model = Techs::findOne($link_id);
				break;
			case 'places_id':
				$model = Places::findOne($link_id);
				break;
			case 'soft_id':
				$model = Soft::findOne($link_id);
				break;
			default:
				$model=null;
		}
		if ($model === null)
			throw new NotFoundHttpException('The requested page does not exist.');
		
		$model->scans_id=$id;
		$model->save(false);
		
		Yii::$app->response->format = Response::FORMAT_JSON;
		return (object)['code'=>'0'];
	}
	
	
	/**
	 * Deletes an existing Scans model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param int|null $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id=null)
    {
    	if (is_null($id)) $id=Yii::$app->request->post('key');
    	
    	/** @var Scans $model */
    	$model=$this->findModel($id);
    	$model->contracts_id=null;
		$model->places_id=null;
		$model->tech_models_id=null;
		$model->material_models_id=null;
		$model->lic_types_id=null;
		$model->lic_items_id=null;
		$model->arms_id=null;
		$model->techs_id=null;
		$model->soft_id=null;
        $model->save();

        //вместо удаления отвязываем ото всех и сохраняем. Осиротевший скан может понадобиться при работе с журналами
        //if (file_exists($_SERVER['DOCUMENT_ROOT'].$model->fullFname))
        //    unlink($_SERVER['DOCUMENT_ROOT'].$model->fullFname);

	    if (Yii::$app->request->isAjax) {
		    Yii::$app->response->format = Response::FORMAT_JSON;
		    return (object)['code'=>'0'];
	    }
	    return $this->redirect(['index']);
    }
    
}
