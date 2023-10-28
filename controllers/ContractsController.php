<?php

namespace app\controllers;

use app\models\Users;
use Exception;
use Throwable;
use Yii;
use app\models\Contracts;
use yii\db\StaleObjectException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;


/**
 * ContractsController implements the CRUD actions for Contracts model.
 */
class ContractsController extends ArmsBaseController
{
	public $modelClass='\app\models\Contracts';
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['hint-arms','hint-parent','scans'],
			'edit'=>['update-form','unlink','link','link-tech','scan-upload']
		]);
	}
	
	
	
	/**
	 * Возвращает IDs армов с переданными документами
	 * @param $ids
	 * @param $form
	 * @return mixed
	 */
	public function actionHintArms($ids,$form)
	{
		return Yii::$app->formatter->asRaw(Contracts::fetchArmsHint($ids,$form));
	}
	
	/**
	 * Возвращает IDs армов с переданными документами
	 * @param $ids
	 * @param $form
	 * @return mixed
	 */
	public function actionHintParent($ids,$form)
	{
		return Yii::$app->formatter->asRaw(Contracts::fetchParentHint($ids,$form));
	}
	
	

	/**
	 * Displays a single Contracts model.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionScans(int $id)
	{
		return $this->renderAjax('scans', [
			'model' => $this->findModel($id),
		]);
	}
	
	/**
	 * Creates a new Contracts model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
    public function actionCreate()
    {
	    $model = new Contracts();
	
		//передали родительский документ
		$model->load(Yii::$app->request->get());
	
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			if (Yii::$app->request->isAjax) {
				Yii::$app->response->format = Response::FORMAT_JSON;
				return ['error'=>'OK','code'=>0,'model'=>$model];
			} else {
				return $this->redirect([Yii::$app->request->get('apply')?'update':'view', 'id' => $model->id]);
			}
		} elseif (Yii::$app->request->isPost && Yii::$app->request->isAjax) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$result = [];
			foreach ($model->getErrors() as $attribute => $errors) {
				$result[yii\helpers\Html::getInputId($model, $attribute)] = $errors;
			}
			return ['error'=>'ERROR','code'=>1,'validation'=>$result];
			
		} elseif (Yii::$app->request->isAjax) {
			return $this->renderAjax('create', [
				'model' => $model,
				'modalParent' => '#modal_form_loader'
			]);
		}
	
	
		return $this->render('create', [
			'model' => $model,
		]);


    }


	/**
	 * Updates an existing Contracts model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdate(int $id)
	{
		$model = $this->findModel($id);

		//обработка аякс запросов
		if (Yii::$app->request->isAjax) {
			Yii::$app->response->format = Response::FORMAT_JSON;

			if ($model->load(Yii::$app->request->post()) && $model->save()) {
				return ['error'=>'OK','code'=>0,'model'=>$model];
			} elseif(Yii::$app->request->isPost) {
				$result = [];
				foreach ($model->getErrors() as $attribute => $errors) {
					$result[yii\helpers\Html::getInputId($model, $attribute)] = $errors;
				}
				return ['error'=>'ERROR','code'=>1,'validation'=>$result];
			}
			return $this->renderAjax('update', [
				'model' => $model,
				'modalParent' => '#modal_form_loader'
			]);
		}

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->redirect([Yii::$app->request->get('apply')?'update':'view', 'id' => $model->id]);
		}

		return $this->render('update', [
			'model' => $model,
		]);
	}

	/**
	 * Updates an existing Contracts model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @noinspection PhpUnusedFunctionInspection
	 */
	public function actionUpdateForm(int $id)
	{
		return $this->renderAjax('_form', [
			'model' => $this->findModel($id),
		]);
	}

	/**
	 * Deletes an existing Contracts model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @throws Exception
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
    public function actionDelete(int $id)
    {
	    if (!Users::isAdmin()) {throw new  ForbiddenHttpException('Access denied');}
	
	    $model=$this->findModel($id);

    	//ищем и удаляем все привязанные сканы
    	$scans=$model->scans;
    	if (is_array($scans) && count($scans)) {
    		foreach ($scans as $scan) {
    			$scan->delete();
		    }
	    }
        $this->findModel($id)->delete();


        return $this->redirect(['index']);
    }

	public function actionScanUpload()
	{
		$id=Yii::$app->request->post('contracts_id');
		if (is_null($id))
			return "{\"error\":\"Невозможно прикрепить сканы к еще не созданному документу. Нажмите сначала кнопку &quot;Применить&quot;\"}";
		else
			return "{\"error\":\"Якобы сохранено в модель $id\"}";	}

	/**
	 * Отвязывает документ от объекта.
	 * @param int $id
	 * @param int $model_id
	 * @param string $link
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUnlink(int $id, int $model_id, string $link)
	{
		//признак что документ был связан с объектом
		$usage=false;
		//признак что был отвязан в результате
		$usage_deleted=false;

		$contract=$this->findModel($id);
		$model_ids=$contract->$link;
		
		if (array_search($model_id,$model_ids)!==false) {
			$usage=true;
			$contract->$link=array_diff($model_ids,[$model_id]);
			if ($contract->save()) $usage_deleted=true;
		}

		Yii::$app->response->format = Response::FORMAT_JSON;
		if ($usage) {
			if ($usage_deleted) {
				return ['error'=>'OK','code'=>'0','Message'=>'Usage removed'];
			} else {
				return ['error'=>'ERROR','code'=>'1','Message'=>'Link removing error'];
			}
		} else {
			return ['error'=>'OK','code'=>'2','Message'=>'Requested usage not found ['.implode(',',$model_ids).']'];
		}
	}
	
	/**
	 * Отвязывает документ от объекта.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param int $id
	 * @param int $model_id
	 * @param string $link
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionLink(int $id, int $model_id, string $link)
	{
		
		$contract=$this->findModel($id);
		$model_ids=$contract->$link;
		if (array_search($model_id,$model_ids)===false) {
			$model_ids[]=$model_id;
			$contract->$link=$model_ids;
			$contract->save();
		}

		Yii::$app->response->format = Response::FORMAT_JSON;
		return ['error'=>'OK','code'=>'0','Message'=>'Added'];

	}

	/**
	 * Отвязывает документ от объекта.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param int $id
	 * @param int $techs_id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionLinkTech(int $id, int $techs_id)
	{
		$model=$this->findModel($id);
		$techs_ids=$model->techs_ids;
		if (array_search($techs_id,$techs_ids)===false) {
			$techs_ids[]=$techs_id;
			$model->techs_ids=$techs_ids;
			$model->save();
		}

		Yii::$app->response->format = Response::FORMAT_JSON;
		return ['error'=>'OK','code'=>'0','Message'=>'Added'];

	}

	/**
     * Finds the Contracts model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id
     * @return Contracts the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int $id)
    {
        if (($model = Contracts::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
