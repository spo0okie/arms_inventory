<?php

namespace app\modules\api\controllers;

use app\helpers\StringHelper;
use app\models\Networks;
use yii\web\NotFoundHttpException;


class NetIpsController extends BaseRestController
{
	public $modelClass='app\models\NetIps';
	public function accessMap()
	{
		$class=StringHelper::class2Id($this->modelClass);
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['first-unused'],
			"view-$class"=>['first-unused'],
		]);
	}
	
	public static $searchFields=[	//набор полей по которым можно делать серч с мапом в SQL поля
		'name'=>'name',
		'addr'=>'text_addr',
		'comment'=>'comment',
	];
 
	public function actionFirstUnused($text_addr){
    	if (!is_object($network=Networks::find()->where(['text_addr'=>$text_addr])->one()))  {
			throw new NotFoundHttpException("Network $text_addr not found");
		}
    	/* @var \app\models\Networks $network */
		return $network->firstUnusedIp;
	}
}
