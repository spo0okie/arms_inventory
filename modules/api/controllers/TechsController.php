<?php

namespace app\modules\api\controllers;

use app\models\Techs;
use app\models\Users;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

/**
 * Class TechsController
 * @package app\modules\api\controllers
 * @noinspection PhpUnusedElement
 */
class TechsController extends BaseRestController
{
    
    public $modelClass='app\models\Techs';
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['search-by-mac','search-by-user']
		]);
	}
	
	/**
	 * @param $mac
	 * @return ActiveRecord
	 * @throws NotFoundHttpException
	 * @noinspection PhpUnusedElement
	 */
	public function actionSearchByMac($mac){
		//ищем телефонный аппарат по номеру
		$tech = Techs::find()
			->where(['mac' => $mac ])
			->one();
		//если нашли
		if (is_object($tech)){
			//он прикреплен к АРМ?
			return $tech;
		}
		throw new NotFoundHttpException("not found");
	}
	
	/**
	 * @param $id
	 * @return string
	 * @throws NotFoundHttpException
	 */
	public function actionSearchByUser($id){
		//ищем пользователя
		$user = Users::findOne($id);
		/**
		 * @var $user Users
		 */
		//если нашли
		if (is_object($user)){
			//он прикреплен к АРМ?
			$arms=$user->techs;
			if (is_array($arms)) {
				//перебираем армы
				foreach ($arms as $arm){
					//ищем у них телефоны
					$phones=$arm->voipPhones;
					if (is_array($phones)) foreach ($phones as $phone) {
						if (strlen($phone->comment) && (int)$phone->comment) return $phone->comment;
					}
					if ($arm->isVoipPhone && strlen($arm->comment) && (int)$arm->comment) {
						return $arm->comment;
					}
				}
			}
		}
		throw new NotFoundHttpException("not found");
	}

}
