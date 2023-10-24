<?php

namespace app\modules\api\controllers;




use app\controllers\ArmsBaseController;
use Yii;
use yii\web\IdentityInterface;

class UsersController extends BaseRestController
{
    
    public $modelClass='app\models\Users';
    
    public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			ArmsBaseController::PERM_AUTHENTICATED=>['whoami']
		]);
	}
	
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
	 * возвращает идентификатор авторизованного пользователя
	 * @return IdentityInterface|null
	 */
	public function actionWhoami() {
		return Yii::$app->user->identity;
	}
}
