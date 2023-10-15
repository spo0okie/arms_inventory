<?php

namespace app\modules\api\controllers;

use app\controllers\ArmsBaseController;
use app\models\Techs;
use app\models\Users;
use yii\web\NotFoundHttpException;


class PhonesController extends BaseRestController
{
	
	public $viewActions=['search-by-user','search-by-num'];
	public function accessMap()
	{
		return [
			'view'=>$this->viewActions,
			'view-phones'=>$this->viewActions,
			ArmsBaseController::PERM_ANONYMOUS=>[]
		];
	}
	
	public function actions(){
		return $this->viewActions;
	}
	
	public function actionSearchByNum($num){
		//ищем телефонный аппарат по номеру
		$tech = \app\models\Techs::find()
			->where(['comment' => $num ])
			->one();
		/**
		 * @var $tech Techs
		 */
		//если нашли
		if (is_object($tech)){
			//он прикреплен к АРМ?
			if (is_object($arm=$tech->arm)) {
				//пользователь у АРМа есть?
				if (is_object($user=$arm->user)) {
					return $user->Ename;
				}
			}
			if (is_object($user=$tech->user)) {
				return $user->Ename;
			}
		}
		$user= Users::find()
			->where([
				'phone'=>$num,
				'Uvolen'=>false
			])
			->one();
		/**
		 * @var $user Users
		 */
		if (is_object($user))
			return $user->Ename;
		
		throw new NotFoundHttpException("not found");
	}
	
	public function actionSearchByUser($id=null,$login=null){
		//ищем пользователя
		if ($id)
			$user = Users::findOne($id);
		elseif ($login)
			$user = Users::find()
			->where(['Login'=>$login])
			->one();
		/**
		 * @var $user Users
		 */

		//если нашли
		//var_dump($user);
		$return=[];
		if (is_object($user)){
			//он прикреплен к АРМ?
			$techs=$user->techs;
			//var_dump($arms);
			if (is_array($techs)) {
				//перебираем армы
				foreach ($techs as $tech) {
					//ищем у них телефоны
					$phones = $tech->voipPhones;
					//var_dump($phones);
					if (is_array($phones)) foreach ($phones as $phone) {
						if (strlen($phone->comment) && (int)$phone->comment)
							$return[(int)$phone->comment] = (int)$phone->comment;
					}
					if ($tech->isVoipPhone && strlen($tech->comment) && (int)$tech->comment) {
						$return[(int)$tech->comment] = (int)$tech->comment;
					}
				}
			}
			if (count($return))
				return implode(', ',$return);
			else
				return $user->Phone;
		} else
		throw new NotFoundHttpException("not found");
	}

}
