<?php

namespace app\controllers;

use app\models\Contracts;
use app\models\LicKeys;
use app\models\links\LicLinks;
use Yii;
use app\models\LicItems;
use yii\data\ArrayDataProvider;
use yii\web\NotFoundHttpException;
use yii\data\ActiveDataProvider;
use yii\web\Response;


/**
 * LicItemsController implements the CRUD actions for LicItems model.
 */
class LicItemsController extends ArmsBaseController
{

	public $modelClass=LicItems::class;
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['hint-arms','contracts'],
		]);
	}
	
	/**
	 * Возвращает IDs оборудования связанного с закупкой (через документы)
	 * @param int    $id
	 * @param string $form
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionHintArms(int $id, string $form)
	{
		if ($model=$this->findModel($id)) {
			/** @var $model LicItems */
			return Yii::$app->formatter->asRaw(Contracts::fetchArmsHint($model->contracts_ids,$form));
		}
		return null;
	}


	/**
	 * Displays a single Arms model.
	 * @param int $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionContracts(int $id)
	{
		return $this->renderAjax('contracts', ['model' => $this->findModel($id)]);
	}


	/**
     * Displays a single LicItems model.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id)
    {
	
		return $this->render('view', [
            'model' => $this->findModel($id),
	        'keys' => new ActiveDataProvider([
		        'query' => LicKeys::find()->where(['lic_items_id'=>$id]),
	        ]),
			'linksData'=>new ArrayDataProvider([
				'allModels' => LicLinks::findForLic('items',$id),
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
			])
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
		/** @var LicItems $model */
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
