<?php

namespace app\modules\api\controllers;

use app\models\Comps;
use app\models\CompsSearch;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\web\BadRequestHttpException;
use OpenApi\Attributes as OA;

class CompsController extends BaseRestController
{
	
	public function accessMap(): array
	{
		return array_merge_recursive(parent::accessMap(),[
			'update-comps'=>['push']
		]);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		$behaviors=parent::behaviors();
		$behaviors['verbFilter']['actions']['push']=['POST'];
		$behaviors['verbFilter']['actions']['update']=['POST','PUT','PATCH'];
		return $behaviors;
	}
	
	public $modelClass='app\models\Comps';
	
	public static array $searchFields=[
		'name'=>'name',
		'ip'=>'ip',
		'mac'=>'mac',
	];
	
	public function actionSearch($name=null,$domain=null,$ip=null): ActiveRecord {
		if ($name) return \app\controllers\CompsController::searchModel($name,$domain,$ip);
		return parent::actionSearch();
	}
	
	public function actionFilter(): ActiveDataProvider
	{
		$searchModel = new CompsSearch();
		$searchModel->archived= Yii::$app->request->get('showArchived',false);
		return new ActiveDataProvider(['query'=>$searchModel->search(Yii::$app->request->queryParams)]);
    }
	
	#[OA\Post(
		path: "/web/api/{controller}/push",
		summary: "Обновить (если в теле передан ID) или создать новый элемент ОС (если ID не заполнен)",
		requestBody: new OA\RequestBody(
			required: true,
			content: new OA\MediaType(
				mediaType: "application/json",
				schema: new OA\Schema(ref: "#/components/schemas/{model}")
			),
		),
		responses: [
			new OA\Response(
				response: 200,
				description: "OK (создано)",
				content: new OA\MediaType(
					mediaType: "application/json",
					schema: new OA\Schema(ref: "#/components/schemas/{model}")
				),
			),
			new OA\Response(
				response: 201,
				description: "OK (обновлено)",
				content: new OA\MediaType(
					mediaType: "application/json",
					schema: new OA\Schema(ref: "#/components/schemas/{model}")
				),
			),
			new OA\Response(response: 422, description: "Предоставлены неверные данные"),
		]
	)]
    public function actionPush() {
    	/** @var Comps $loader */
		$loader = new $this->modelClass();
	
		//грузим переданные данные
		if (!$loader->load(Yii::$app->getRequest()->getBodyParams(),'')) {
			throw new BadRequestHttpException("Error loading posted data");
		}
		
		//передали ID?
		if ($loader->id) {
			return $this->runAction('update',['id'=>$loader->id]);
		}
		
		$search=Comps::findByAnyName($loader->name,'workgroup');
		if (is_object($search)&&$search->id) {
			return $this->runAction('update',['id'=>$search->id]);
		}
	
		return $this->runAction('create');
	}
}
