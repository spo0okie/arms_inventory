<?php

namespace app\modules\api\controllers;

use app\controllers\ArmsBaseController;
use app\models\Techs;
use app\models\Users;
use yii\web\NotFoundHttpException;
use OpenApi\Attributes as OA;

/**
 * @OA\Tag(
 *   name="{controller}",
 *   description="Телефоны пользователей"
 * )
 */
class PhonesController extends BaseRestController
{
	
	public function disabledActions(): array
	{
		return ['index','view','update','create','delete','search','filter'];
	}
	public $viewActions=['search-by-user','search-by-num'];
	public function accessMap(): array
	{
		return [
			'view'=>$this->viewActions,
			'view-phones'=>$this->viewActions,
			ArmsBaseController::PERM_ANONYMOUS=>[],
		];
	}
	
	public function actions(){
		return $this->viewActions;
	}
	
	#[OA\Get(
		path: "/web/api/phones/search-by-num",
		summary: "Поиск имени пользователя по внутреннему номеру телефона",
		parameters: [new OA\Parameter(
			name: "num",
			description: "Искомый номер телефона",
			in: "query",
			required: true,
			schema: new OA\Schema(type: "string")
		)],
		responses: [
			new OA\Response(
				response: 200,
				description: "OK",
				content: new OA\MediaType(
					mediaType: "text/plain",
					schema: new OA\Schema(
						description: "Полные ФИО пользователя",
						type: "string",
						example: "Иванов Иван Иванович"
					)
				)
			),
			new OA\Response(response: 404, description: "Не найдено"),
		]
	)]
	public function actionSearchByNum($num){
		//ищем телефонный аппарат по номеру
		$tech = Techs::find()
			->where(['comment' => $num ])
			->one();
		/**
		 * @var $tech Techs
		 */
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
		$user= Users::find()
			->where([
				'phone'=>$num,
				'Uvolen'=>false,
			])
			->one();
		/**
		 * @var $user Users
		 */
		if (is_object($user))
			return $user->Ename;
		
		throw new NotFoundHttpException("not found");
	}
	
	#[OA\Get(
		path: "/web/api/{controller}/search-by-user",
		summary: "Поиск внутреннего номера телефона по ID или логину пользователя",
		parameters: [
			new OA\Parameter(
				name: "id",
				description: "ID пользователя",
				in: "query",
				required: false,
				schema: new OA\Schema(type: "integer")
			),
			new OA\Parameter(
				name: "login",
				description: "Login пользователя",
				in: "query",
				required: false,
				schema: new OA\Schema(type: "string")
			),
		],
		responses: [
			new OA\Response(
				response: 200,
				description: "OK",
				content: new OA\MediaType(
					mediaType: "text/plain",
					schema: new OA\Schema(
						description: "Внутренний номер телефона пользователя: может быть несколько через запятую с пробелом, а может быть пустая строка (если запрошенный пользователь найден, но телефона у него нет)",
						type: "string",
						example: "1100, 1405"
					)
				)
			),
			new OA\Response(response: 404, description: "Такой пользователь не найден"),
		]
	)]
	public function actionSearchByUser($id=null,$login=null){
		//ищем пользователя
		if ($id)
			$user = Users::findOne($id);
		elseif ($login)
			$user = Users::find()
			->where(['Login'=>$login])
			->one();
		/**
		 * @var $user Users
		 */

		//если нашли
		//var_dump($user);
		$return=[];
		if (is_object($user)){
			//он прикреплен к АРМ?
			$techs=$user->techs;
			//var_dump($arms);
			if (is_array($techs)) {
				//перебираем армы
				foreach ($techs as $tech) {
					//ищем у них телефоны
					$phones = $tech->voipPhones;
					//var_dump($phones);
					if (is_array($phones)) foreach ($phones as $phone) {
						if (strlen($phone->comment) && (int)$phone->comment)
							$return[(int)$phone->comment] = (int)$phone->comment;
					}
					if ($tech->isVoipPhone && strlen($tech->comment) && (int)$tech->comment) {
						$return[(int)$tech->comment] = (int)$tech->comment;
					}
				}
			}
			if (count($return))
				return implode(', ',$return);
			else
				return $user->Phone;
		} else
		throw new NotFoundHttpException("not found");
	}

}
