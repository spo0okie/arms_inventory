<?php

namespace app\modules\api\controllers;




class UsersController extends \yii\rest\ActiveController
{
    
    public $modelClass='app\models\Users';
    
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['view']);
		unset($actions['index']);
	    return $actions;
        //return ['view','update'];
    }

	public function actionView($id='',$num='',$name='',$org='',$login='',$mobile=''){
		/**
		 * ТЗ по поиску примерно следующее
		 * нужно найти все записи о пользователе со всеми вариантами трудоустройства
		 * сформировать из этого табличку всех типов трудоустройтсв сотрудника (при наличии 2х трудоустройств одного типа предпочитать активное (не уволен))
		 *
		 */
		//ищем телефонный аппарат по номеру
		$user = \app\models\Users::find()
			->andFilterWhere(['id' => $id])
			->andFilterWhere(['Ename' => $name])
			->andFilterWhere(['employee_id' => $num])
			->andFilterWhere(['login' => $login])
			->andFilterWhere(['org_id' => $org])
			->andFilterWhere(['like',['Mobile',$mobile]])
			->orderBy([
				'Uvolen'=>SORT_ASC,
				'Persg'=>SORT_ASC,
				])
			->one();
		//если нашли
		if (is_object($user)) return $user;

		//Допустим не нашли, но у нас есть имя состоящее из 3х токенов
		//тогда мы можем предположить, что имена записаны не как ФИО, а ИОФ
		if (strlen($name)) {
			$tokens=explode(' ',$name);
			$FIO=$tokens[2].' '.$tokens[0].' '.$tokens[1];
			$user = \app\models\Users::find()
				->andFilterWhere(['id' => $id])
				->andFilterWhere(['Ename' => $FIO])
				->andFilterWhere(['employee_id' => $num])
				->andFilterWhere(['login' => $login])
				->andFilterWhere(['org_id' => $org])
				->orderBy([
					'Uvolen'=>SORT_ASC,
					'Persg'=>SORT_ASC,
				])
				->one();
			//если нашли
			if (is_object($user)) return $user;
		}
		throw new \yii\web\NotFoundHttpException("not found");
	}

}
