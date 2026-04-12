<?php

namespace app\modules\api\controllers;

use app\helpers\StringHelper;
use app\models\Networks;
use yii\web\NotFoundHttpException;

/**
 * Class NetIpsController
 * @package app\modules\api\controllers
 * @noinspection PhpUnusedElementInspection
 */
class NetIpsController extends BaseRestController
{
	public $modelClass='app\models\NetIps';
	public function accessMap(): array
	{
		$class=StringHelper::class2Id($this->modelClass);
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['first-unused'],
			"view-$class"=>['first-unused'],
		]);
	}
	
	public static array $searchFields=[	//набор полей по которым можно делать поиск с маппингом в SQL поля
		'name'=>'name',
		'addr'=>'text_addr',
		'comment'=>'comment',
	];
 
	/**
	 * Возвращает первый свободный IP-адрес в указанной сети.
	 * Ищет сеть по текстовому адресу (CIDR или IP-диапазон) и возвращает
	 * первый незанятый адрес через Networks::$firstUnusedIp.
	 *
	 * GET-параметры:
	 * @param string $text_addr  Текстовое представление сети (поле Networks.text_addr, например '192.168.1.0/24')
	 *
	 * @return mixed  Первый свободный IP-адрес
	 * @throws NotFoundHttpException если сеть с таким адресом не найдена
	 */
	public function actionFirstUnused($text_addr){
    	if (!is_object($network=Networks::find()->where(['text_addr'=>$text_addr])->one()))  {
			throw new NotFoundHttpException("Network $text_addr not found");
		}
    	/* @var Networks $network */
		return $network->firstUnusedIp;
	}
}
