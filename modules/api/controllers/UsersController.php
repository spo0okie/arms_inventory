<?php

namespace app\modules\api\controllers;




class UsersController extends BaseRestController
{
    
    public $modelClass='app\models\Users';
	
	public static $searchFields=[
		'id',
		'Ename'=>'Ename',
		'name'=>'Ename',
		'employee_id'=>'employee_id',
		'num'=>'employee_id',
		'login'=>'Login',
		'org'=>'org_id',
		'org_id'=>'org_id',
		'uid'=>'uid'
	];
	
	public static $searchFieldsLike=[
		'mobile'=>'mobile'
	];
	
	public static $searchOrder=[
		'Uvolen'=>SORT_ASC,
		'Persg'=>SORT_ASC,
	];
	
	/**
	 * ТЗ по поиску примерно следующее
	 * нужно найти все записи о пользователе со всеми вариантами трудоустройства
	 * сформировать из этого табличку всех типов трудоустройтсв сотрудника (при наличии 2х трудоустройств одного типа предпочитать активное (не уволен))
	 *
	 */

	/*public function actionView($id='',$num='',$name='',$org='',$login='',$mobile='',$uid=''){
		//ищем пользователя
		$user = \app\models\Users::find()
			->andFilterWhere(['id' => $id])
			->andFilterWhere(['Ename' => $name])
			->andFilterWhere(['employee_id' => $num])
			->andFilterWhere(['login' => $login])
			->andFilterWhere(['org_id' => $org])
			->andFilterWhere(['uid' => $uid])
			->andFilterWhere(['like','Mobile',$mobile])
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
	}*/

}
