<?php

namespace app\modules\api\controllers;

use app\models\Domains;


/*
 * Это функция из первоначального класса работы с пользователями (когда он мог брать источник данных из SAP или 1С)
public function getMainEmployment($filter){

	//ищем всех пользователей по критерию из фильтра
	$users=$this->findIdsBy($filter);
	if (!count($users)) $users=$this->findIdsBy($filter,true,false,true); //если не нашлось, то ищем без учета буквы Ё
	if (!count($users)) return null;

	//составляем из найденных трудоустройств таблицу [табельный номер=>тип]
	$types=[];

	foreach ($users as $user) {
		$type=(int)$this->getItemField($user, 'Persg');

		if ($type>4) continue; //исключаем трудоустройство по ДГПХ

		if (($exst=(array_search($type,$types)))!==false) { //ситуация, когда трудоустройство этого типа уже есть

			$user_active=($this->getItemField($user, 'Uvolen')!='1'); //текущий пользователь не уволен?
			$exst_active=($this->getItemField($exst, 'Uvolen')!='1'); //ранее найденный пользователь не уволен?

			if ($user_active xor $exst_active) {//ситуация когда один работает а другой нет
				if ($exst_active) continue; //работает ранее найденный? - пропускаем текущего
				unset($types[$exst]); //иначе убираем ранее найденного из нашего списка
			} elseif (!$user_active and !$exst_active) { //ситуация когда оба нашлись и оба уволены
				unset($types[$exst]); //убираем ранее найденного из нашего списка
			} else { //вот тут у нас начинается неразбериха, либо оба уволенные, либо оба работают. в общем равнозначные
				if ($type===1) {
					//обработка исключительной ситуации если фильтр нашел более одного основного трудоустройства (однофамильцы)
					throw new Exception(EMPLOYMENTSEARCH_MANY_RESULTS_TEXT,EMPLOYMENTSEARCH_MANY_RESULTS);
				}
			}
		}



		$types[$user]=$type;
	}

	//выбираем из таблицы трудоустройство наименьшего типа
	return array_search(min($types),$types);
}
*/

class UsersController extends \yii\rest\ActiveController
{
    
    public $modelClass='app\models\Users';
    
    public function actions()
    {
        //$actions = parent::actions();
        //$actions['search'];
        return ['search'];
    }

	public function actionView($num='',$name='',$org='',$login=''){
		/**
		 * ТЗ по поиску примерно следующее
		 * нужно найти все записи о пользователе со всеми вариантами трудоустройства
		 * сформировать из этого табличку всех типов трудоустройтсв сотрудника (при наличии 2х трудоустройств одного типа предпочитать активное (не уволен))
		 *
		 */
		//ищем телефонный аппарат по номеру
		$user = \app\models\Users::find()
			->andFilterWhere(['Ename' => $name])
			->andFilterWhere(['employee_id' => $num])
			->andFilterWhere(['login' => $login])
			->andFilterWhere(['org_id' => $org])
			->orderBy([
				'Uvolen'=>SORT_ASC,
				'Persg'=>SORT_ASC,
				])
			->one();
		//если нашли
		if (is_object($user)){
			//$user['vorna']='test';
			return $user;
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
