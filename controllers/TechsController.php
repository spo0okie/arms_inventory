<?php

namespace app\controllers;

use app\models\HwListItem;
use app\models\Manufacturers;
use Yii;
use app\models\Techs;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * TechsController implements the CRUD actions for Techs model.
 * @noinspection PhpUnused
 */
class TechsController extends ArmsBaseController
{
	public $modelClass='app\models\Techs';
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['ttip-hw','inv-num','passport'],
			'edit'=>['uploads','unlink','updhw','rmhw','edithw','port-list'],
		]);
	}
	
	/**
	 * Displays a tooltip for hw of single model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionTtipHw(int $id)
	{
		return $this->renderPartial('ttip-hw', [
			'model' => $this->findModel($id),
		]);
	}
	
	public function actionItemByName($name)
	{
		if (($model = Techs::findOne(['num'=>$name])) !== null) {
			return $this->renderPartial('item', [
				'model' => $model,
			]);
		}
		throw new NotFoundHttpException('The requested tech not found');
	}


	/**
	 * Формирует префикс и возвращает следующий инвентарный номер в этом префиксе
	 * @param null|int $model_id
	 * @param null|int $place_id
	 * @param null|int $org_id
	 * @param null|int $arm_id
	 * @param null|int $installed_id
	 * @return mixed
	 */
	public function actionInvNum($model_id=null,$place_id=null,$org_id=null,$arm_id=null,$installed_id=null)
	{
		$prefix=Techs::genInvPrefix($model_id,$place_id,$org_id,$arm_id,$installed_id);
		Yii::$app->response->format = Response::FORMAT_JSON;
		return Techs::fetchNextNum($prefix);
	}


    /**
     * Displays a single Techs model.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionPassport(int $id)
    {
        return $this->render('passport', [
            'model' => $this->findModel($id),
        ]);
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
	
	
	/**
	 * Returns tech available network ports
	 * @return array
	 * @throws NotFoundHttpException
	 */
	public function actionPortList()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;
		if (isset($_POST['depdrop_parents'])) {
			$parents = $_POST['depdrop_parents'];
			if ($parents != null) {
				/** @var Techs $model */
				$model=$this->findModel($parents[0]);
				//$out = self::getSubCatList($cat_id);
				// the getSubCatList function will query the database based on the
				// cat_id and return an array like below:
				// [
				//    ['id'=>'<sub-cat-id-1>', 'name'=>'<sub-cat-name1>'],
				//    ['id'=>'<sub-cat_id_2>', 'name'=>'<sub-cat-name2>']
				// ]
				return ['output'=>$model->ddPortsList, 'selected'=>''];
			}
		}
		return ['output'=>'', 'selected'=>''];
	}
	
	/**
	 * Обновляем элемент оборудования
	 * @param $id
	 * @return string|Response
	 * @throws NotFoundHttpException
	 */
	public function actionUpdhw($id){
		
		/** @var Techs $model */
		$model = $this->findModel($id);
		
		//проверяем передан ли uid
		$uid=Yii::$app->request->get('uid',null);
		if (strlen($uid)) {
			if ($uid==='sign-all') { //специальная команда на подпись всего оборудования
				//error_log('signing all');
				$model->hwList->signAll();
			}else {
				$newItem = new HwListItem();
				$newItem->loadArr($_GET);
				$model->hwList->add($newItem);
			}
			//error_log('saving');
			//сохраняем без проверки валидности, т.к. пользователь не может изменить данные
			if (!$model->save(false)) error_log(print_r($model->errors,true));
		}
		
		return $this->redirect(['passport', 'id' => $model->id]);
	}
	
	/**
	 * Обновляем элемент оборудования
	 * @param $id
	 * @return string|Response
	 * @throws NotFoundHttpException
	 */
	public function actionEdithw($id){
		
		$manufacturers= Manufacturers::fetchNames();
		/** @var Techs $model */
		$model = $this->findModel($id);
		
		//проверяем передан ли uid
		$uid=Yii::$app->request->get('uid',null);
		$editItem=null;
		foreach ($model->hwList->items as $pos=>$item) {
			if ($item->uid == $uid) $editItem=$item;
		}
		if (!$editItem) $editItem = new HwListItem();
		
		return Yii::$app->request->isAjax?
		$this->renderAjax( '/hwlist/edit-item',
			[
				'item'=>$editItem,
				'model'=>$model,
				'manufacturers'=>$manufacturers,
				'modalParent' => '#modal_form_loader'
			]):
		$this->render( '/hwlist/edit-item',
			[
				'item'=>$editItem,
				'model'=>$model,
				'manufacturers'=>$manufacturers,
			]);
	}
	
	/**
	 * Удаляем элемент оборудования
	 * @param $id
	 * @return string|Response
	 * @throws NotFoundHttpException
	 */
	public function actionRmhw($id){
		
		/** @var Techs $model */
		$model = $this->findModel($id);
		
		//проверяем передан ли uid
		if (strlen(Yii::$app->request->get('uid',null))) {
			$model->hwList->del(Yii::$app->request->get('uid'));
			//сохраняем без проверки валидности, т.к. пользователь не может изменить данные
			$model->save(false);
		}
		
		return $this->redirect(['passport', 'id' => $model->id]);
	}
}
