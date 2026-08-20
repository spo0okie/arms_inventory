<?php

namespace app\modules\api\controllers;




use app\controllers\ArmsBaseController;
use app\generation\ModelFactory;
use app\models\Users;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\IdentityInterface;
use yii\web\NotFoundHttpException;
use OpenApi\Attributes as OA;

class UsersController extends BaseRestController
{

    public $modelClass='app\models\Users';

    public function accessMap(): array
	{
		return array_merge_recursive(parent::accessMap(),[
			ArmsBaseController::PERM_AUTHENTICATED=>['whoami'],
			//migrate меняет данные, поэтому пускаем по тем же правам, что create/update/delete:
			//глобальное edit (роли editor/admin) либо точечное update-users
			ArmsBaseController::PERM_EDIT=>['migrate'],
			'update-users'=>['migrate'],
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		$behaviors=parent::behaviors();
		$behaviors['verbFilter']['actions']['migrate']=['POST'];
		return $behaviors;
	}
	
	public static array $searchFields=[
		'id',
		'Ename'=>'Ename',
		'name'=>'Ename',
		'employee_id'=>'employee_id',
		'num'=>'employee_id',
		'login'=>'Login',
		'org'=>'org_id',
		'org_id'=>'org_id',
		'uid'=>'uid',
		'uvolen'=>'Uvolen',
	];
	
	public static array $searchFieldsLike=[
		'mobile'=>'mobile'
	];
	
	public static array $searchOrder=[
		'Uvolen'=>SORT_ASC,
		'Persg'=>SORT_ASC,
	];
	
	/**
	 * Возвращает идентификатор авторизованного пользователя
	 * @return IdentityInterface|null
	 */
	public function actionWhoami() {
		return Yii::$app->user->identity;
	}

	#[OA\Post(
		path: "/api/users/migrate",
		summary: "Перенести связи и атрибуты одной кадровой записи сотрудника на другую",
		parameters: [
			new OA\Parameter(
				name: "id",
				description: "ID записи-источника (у нее забирают данные и очищают логин)",
				in: "query",
				required: true,
				schema: new OA\Schema(type: "integer")
			),
			new OA\Parameter(
				name: "target",
				description: "ID записи-приемника (на нее переносятся данные)",
				in: "query",
				required: true,
				schema: new OA\Schema(type: "integer")
			),
		],
		responses: [
			new OA\Response(
				response: 200,
				description: "OK",
				content: new OA\MediaType(
					mediaType: "application/json",
					schema: new OA\Schema(ref: "#/components/schemas/{model}(read)")
				)
			),
			new OA\Response(response: 400, description: "Источник и приемник совпадают"),
			new OA\Response(response: 404, description: "Запись не найдена"),
		]
	)]
	/**
	 * Переносит связи и атрибуты одной кадровой записи сотрудника на другую.
	 *
	 * Это тот же механизм, который срабатывает сам при совпадении логина и uid
	 * ({@see Users::afterSave()} -> {@see Users::absorbUser()}), но вызываемый по требованию:
	 * у одного человека бывает несколько записей (разные трудоустройства), и при переводе
	 * все накопленное на прежней записи (телефон, техника, ПК, лицензии, доступы, журнал входов)
	 * должно уехать на новую, не дожидаясь совпадения логинов.
	 *
	 * Переносятся обратные ссылки (целиком) и значения, помеченные в модели как absorb
	 * (в основном 'ifEmpty' - только поверх пустого поля приемника). Логин источника очищается,
	 * сама запись источника сохраняется (не удаляется).
	 *
	 * POST-параметры:
	 * @param int $id     ID записи-источника
	 * @param int $target ID записи-приемника
	 *
	 * @return Users запись-приемник после переноса
	 * @throws BadRequestHttpException если источник и приемник - одна и та же запись
	 * @throws NotFoundHttpException если запись источника или приемника не найдена
	 * @throws \Throwable
	 */
	public function actionMigrate(int $id, int $target): Users
	{
		$this->checkDisabledActions('migrate');

		if ($id===$target) {
			throw new BadRequestHttpException('Source and target are the same record');
		}

		/** @var Users $source */
		$source=Users::findOne($id);
		if (!is_object($source)) throw new NotFoundHttpException("User $id not found");

		/** @var Users $destination */
		$destination=Users::findOne($target);
		if (!is_object($destination)) throw new NotFoundHttpException("User $target not found");

		$destination->absorbUser($source);

		return $destination;
	}

	/**
	 * Сценарий приемочного теста для {@see actionMigrate()}: создаем две записи сотрудников,
	 * заполняем источнику логин и мобильный (а приемнику мобильный чистим), переносим данные
	 * и проверяем, что мобильный переехал, а логин источника освободился.
	 *
	 * @return array
	 */
	public function testMigrate(): array
	{
		/** @var Users $source */
		$source=ModelFactory::create(Users::class, []);
		/** @var Users $destination */
		$destination=ModelFactory::create(Users::class, []);

		$mobile='+7(900)000-0000';
		$source->Login='migrate-test-'.$source->id;
		$source->Mobile=$mobile;
		$source->save();

		$destination->Mobile='';
		$destination->save();

		$sourceId=$source->id;
		$destinationId=$destination->id;

		return [[
			'name'     => 'default',
			'method'   => 'POST',
			'route'    => '{controller}/migrate',
			'GET'      => ['id' => $sourceId, 'target' => $destinationId],
			'response' => 200,
			'assert'   => static function (\ApiTester $I) use ($sourceId,$destinationId,$mobile) {
				$migrated=Users::findOne($sourceId);
				$absorbed=Users::findOne($destinationId);
				\PHPUnit\Framework\Assert::assertNotNull($migrated,'запись-источник остается в базе');
				\PHPUnit\Framework\Assert::assertSame('',(string)$migrated->Login,'логин источника освобожден');
				\PHPUnit\Framework\Assert::assertEquals($mobile,$absorbed->Mobile,'мобильный переехал на приемник');
			},
		]];
	}
}
