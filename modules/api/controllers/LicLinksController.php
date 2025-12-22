<?php

namespace app\modules\api\controllers;


use app\models\Comps;
use app\models\Soft;
use app\models\Users;
use http\Exception\BadMethodCallException;
use OpenApi\Attributes as OA;
use app\models\ArmsModel;
use app\models\links\LicLinks;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class LicLinksController extends BaseRestController
{

	public $modelClass='app\models\links\LicLinks';
	public function disabledActions(): array
	{
		return ['index','filter','view','update','create','delete'];
	}
	
	public static function filterQuery(
		ActiveQuery $query,
		string $lic_type,
		$product_id=null,
		$user_login=null,
		$comp_name=null
	): ActiveQuery
	{
		if (!in_array($lic_type,LicLinks::$licTypes)) throw new BadMethodCallException("Incorrect license type given");
		if (!$product_id) throw new BadRequestHttpException("No product ID passed");
		if (!$comp_name && !$user_login) throw new BadRequestHttpException("No login or computer name passed");
		
		$errors=[];
		$comp=$user=null;
		
		//атрибут в котором должна присутствовать наша лицензия (ключ/закупка/тип)
		$ids_attr="lic_{$lic_type}_ids";
		
		if ($comp_name) {
			try {
				$comp=\app\controllers\CompsController::searchModel($comp_name);
				/** @var $comp Comps */
				if ($comp) $query->andWhere(['in', 'id', $comp->$ids_attr]);
			} catch (NotFoundHttpException $e) {
				$errors[]=$e->getMessage();
			}
		}
		
		if ($user_login) {
			/** @var $user Users */
			if (is_object($user= Users::findByLogin($user_login)))
				if ($user) $query->andWhere(['in', 'id', $user->$ids_attr]);
				else {
					$errors[]="User '$user_login' not found";
				}
		}
		
		//Если ни у пользователя ни у компа ничего не нашли, то все. Неудача
		if (!is_object($comp) && !is_object($user))
			throw new NotFoundHttpException(implode(',',$errors));
		
		
		$product=Soft::findOne(['id'=>$product_id]);
		if (!is_object($product))
			throw new NotFoundHttpException("Product with ID $product_id not found");
		
		$licGroupsIds=$product->lic_groups_ids;
		switch ($lic_type) {
			case 'groups':	//для типов лицензий ищем прямого их перечисления в лицензиях ПО
				$query->andWhere(['in','id',$licGroupsIds]);
				break;
			case 'items':	//для закупок ищем все закупки входящие в типы лицензий ПО
				$query->andWhere(['in','lic_group_id',$licGroupsIds]);
				break;
			case 'keys':	//для ключей нужен также join с закупками и фильтр аналогичный предыдущему
				$query->joinWith(['licItem'])->andWhere(['in','lic_items.lic_group_id',$licGroupsIds]);
				break;
		}
		
		return $query;
	}
	
	#[OA\Get(
		path: "/web/api/{controller}/search",
		summary: "Поиск привязки лицензии к объекту.",
		parameters: [
			new OA\Parameter(
				name: "productId",
				description: "ID лицензируемого продукта",
				in: "query",
				required: false,
			),
			new OA\Parameter(
				name: "objectType",
				description: "Тип объекта к которому привязана лицензия (АРМы, пользователи, компьютеры)",
				in: "query",
				required: false,
				schema: new OA\Schema(
					type: "string",
					enum: ['arms','users','comps']
				)
			),
			new OA\Parameter(
				name: "licenseType",
				description: "Тип лицензий к которому привязан объект (тип лицензий, закупка, лиц. ключ)",
				in: "query",
				required: false,
				schema: new OA\Schema(
					type: "string",
					enum: ['groups','items','keys']
				)
			),
		],
		responses: [
			new OA\Response(
				response: 200,
				description: "OK",
				content: new OA\MediaType(
					mediaType: "application/json",
				)
			
			),
			new OA\Response(response: 404, description: "Ничего не найдено")
		]
	)]
	public function actionSearch(
		int $productId=null,
		string $objectType=null,
		string $licenseType=null,
		int $objId=null,
		string $objName=null,
		int $licId=null
	): ActiveRecord {
		//return $productId;
		
		if (!$objId && $objName) {
			$objClass=ucfirst($objectType);
			$objClass="app\\models\\$objClass";
			/** @var ArmsModel $objClass */
			$obj=$objClass::findByAnyName($objName);
			if (!is_object($obj)) {
				throw new NotFoundHttpException("$objectType $objName not found");
			}
			$objId=$obj->id;
		}
		
		//var_dump($this->behaviors());
		
		return LicLinks::findProductLicenses(
			$productId,
			$objectType,
			$licenseType,
			$objId,
			$licId
		);
	}

}
