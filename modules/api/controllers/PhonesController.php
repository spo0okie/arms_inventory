<?php

namespace app\modules\api\controllers;

use app\models\Domains;


class PhonesController extends \yii\rest\ActiveController
{
    
    public $modelClass='app\models\Techs';
    
    public function actions()
    {
        //$actions = parent::actions();
        //$actions['search'];
        return ['search','caller-id'];
    }
	
	public function actionSearch($num){
		//ищем телефонный аппарат по номеру
		$tech = \app\models\Techs::find()
			->where(['comment' => $num ])
			->one();
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
		throw new \yii\web\NotFoundHttpException("not found");
	}
	
	public function actionCallerId($num){
		//ищем телефонный аппарат по номеру
		$tech = \app\models\Techs::find()
			->where(['comment' => $num ])
			->one();
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
		$user=\app\models\Users::find()
			->where([
				'phone'=>$num,
				'Uvolen'=>false
			])
			->one();
		if (is_object($user))
			return $user->Ename;
		
		throw new \yii\web\NotFoundHttpException("not found");
	}
	
	public function actionSearchByUser($id=null,$login=null){
		//ищем пользователя
		if ($id)
			$user = \app\models\Users::findOne($id);
		elseif ($login)
			$user = \app\models\Users::find()
			->where(['Login'=>$login])
			->one();
		//если нашли
		//var_dump($user);
		$return=[];
		if (is_object($user)){
			//он прикреплен к АРМ?
			$arms=$user->arms;
			//var_dump($arms);
			if (is_array($arms)) {
				//перебираем армы
				foreach ($arms as $arm){
				//ищем у них телефоны
				$phones=$arm->voipPhones;
				//var_dump($phones);
				if (is_array($phones)) foreach ($phones as $phone) {
					if (strlen($phone->comment) && (int)$phone->comment)
						$return[(int)$phone->comment]=(int)$phone->comment;
				}
			}}
			if (is_object($techs=$user->techs)) foreach ($techs as $tech)
				if ($tech->isVoipPhone && strlen($tech->comment) && (int)$tech->comment) {
					$return[(int)$tech->comment]=(int)$tech->comment;
			}
			if (count($return))
				return implode(', ',$return);
			else
				return $user->Phone;
		} else
		throw new \yii\web\NotFoundHttpException("not found");
	}

}
