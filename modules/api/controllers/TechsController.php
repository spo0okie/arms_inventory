<?php

namespace app\modules\api\controllers;



class TechsController extends \yii\rest\ActiveController
{
    
    public $modelClass='app\models\Techs';
    
    public function actions()
    {
        //$actions = parent::actions();
        //$actions['search'];
        return ['search'];
    }

	public function actionSearchByMac($mac){
		//ищем телефонный аппарат по номеру
		$tech = \app\models\Techs::find()
			->where(['mac' => $mac ])
			->one();
		//если нашли
		if (is_object($tech)){
			//он прикреплен к АРМ?
			return $tech;
		}
		throw new \yii\web\NotFoundHttpException("not found");
	}

	public function actionSearchByUser($id){
		//ищем пользователя
		$user = \app\models\Users::findOne($id);
		//если нашли
		if (is_object($user)){
			//он прикреплен к АРМ?
			$arms=$user->arms;
			if (is_array($arms)) {
				if (count($arms)>1) {

				}
				//перебираем армы
				foreach ($arms as $arm){
				//ищем у них телефоны
				$phones=$arm->voipPhones;
				if (is_array($phones)) foreach ($phones as $phone) {
					if (strlen($phone->comment) && (int)$phone->comment) return $phone->comment;
				}
			}}
			if (is_object($techs=$user->techs)) foreach ($techs as $tech)
				if ($tech->isVoipPhone && strlen($tech->comment) && (int)$tech->comment) {
				return $tech->comment;
			}
		}
		throw new \yii\web\NotFoundHttpException("not found");
	}

}
