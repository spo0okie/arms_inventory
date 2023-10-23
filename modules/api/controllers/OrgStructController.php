<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 19.04.2020
 * Time: 15:41
 */

namespace app\modules\api\controllers;


class OrgStructController extends BaseRestController
{
	public $modelClass='app\models\OrgStruct';
	//набор полей по которым можно делать поиск с маппингом в SQL поля
	public static $searchFields=[
		'name'=>'name',
		'org_id'=>'org_id',
	];
}
