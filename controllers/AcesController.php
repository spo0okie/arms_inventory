<?php

namespace app\controllers;

use app\components\Forms\assets\ArmsFormAsset;
use app\generation\ModelFactory;
use app\helpers\ArrayHelper;
use app\models\AccessTypes;
use app\models\Aces;
use app\models\Acls;
use app\models\Services;
use Yii;

/**
 * AcesController implements the CRUD actions for Aces model.
 */
class AcesController extends ArmsBaseController
{
	public $modelClass=Aces::class;

	public function disabledActions()
	{
		return ['item-by-name',];
	}


	/**
	 * @inheritdoc
	 */
    public function routeOnDelete($model)
    {
    	/** @var Aces $model */
		$acl=$model->acl;
		$schedules_id=is_object($acl)?$acl->schedules_id:0;
		return ($schedules_id)?
			['/scheduled-access/view','id'=>$schedules_id]:
			['/scheduled-access/index'];
    }

	/**
	 * Создаёт ACE в заданном ACL.
	 *
	 * ACE живет только внутри ACL, поэтому ACL-контекст (`Aces[acls_id]`) обязателен.
	 * Если его нет (например, кнопка «Добавить» в списке доступов), то создавать надо
	 * не только запись доступа, но и сам ACL с ресурсом — этот запрос передается
	 * {@see AclsController::actionCreate()}, который умеет создавать ACL (в том числе
	 * несколько сразу, по одному на выбранный ресурс) вместе с общим ACE.
	 *
	 * @return mixed
	 */
	public function actionCreate()
	{
		$this->view->registerAssetBundle(ArmsFormAsset::class);
		/** @var Aces $model */
		$model = new $this->modelClass();
		$model->load(Yii::$app->request->get());

		//форма без ACL-контекста постится на этот же URL, поэтому ACL ищем и в POST
		if (empty($model->acls_id) && empty(Yii::$app->request->post('Aces')['acls_id'] ?? null)) {
			return Yii::$app->runAction('acls/create');
		}

		//дефолтные типы доступа сервиса-ресурса предзаполняют галочки нового ACE (issue #204);
		//POST-данные формы (включая пустой checkboxList) перекрывают предзаполнение ниже
		if (empty($model->access_types_ids)
			&& is_object($model->acl)
			&& is_object($model->acl->service)
		) {
			$service=$model->acl->service;
			$model->access_types_ids=ArrayHelper::getColumn($service->defaultAccessTypes,'id');
			//сервисные переопределения сетевых параметров (HTTPS на нестандартном порту и т.п.)
			if ($params=$service->defaultIpParams) $model->setIpParams($params);
		}

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->defaultReturn($this->routeOnUpdate($model), [$model]);
		}

		return $this->defaultRender('create', ['model' => $model]);
	}

	/**
	 * Дополняет базовый testCreate сценарием предзаполнения типов доступа (issue #204):
	 * у ACL с ресурсом-сервисом, имеющим дефолтные типы доступа, форма нового ACE
	 * открывается с уже выставленными галочками этих типов.
	 *
	 * @return array
	 */
	public function testCreate(): array
	{
		$scenarios=parent::testCreate();

		try {
			//IP-тип со стандартными параметрами, у сервиса - переопределение (issue #204:
			//PUPPET предоставляет HTTPS на нестандартном порту)
			$type=new AccessTypes();
			$type->name='дефолт-тип #204';
			$type->is_ip=1;
			$type->ip_params_def='TCP 443';
			if (!$type->save()) return $scenarios;

			/** @var Services $service */
			$service=ModelFactory::create(Services::class,['empty'=>true]);
			if (!is_object($service)) return $scenarios;
			$service->defaultIpParams=[$type->id=>'TCP 8140'];
			$service->default_access_types_ids=[$type->id];
			if (!$service->save()) return $scenarios;

			$acl=new Acls();
			$acl->services_id=$service->id;
			if (!$acl->save()) return $scenarios;
		} catch (\Throwable $e) {
			return $scenarios;
		}

		$scenarios[]=[
			'name' => 'service defaults prefill',
			'GET' => ['Aces'=>['acls_id'=>$acl->id]],
			'response' => 200,
			'assert' => static function (\AcceptanceTester $I) use ($type) {
				//чекбокс дефолтного типа доступа отрендерен выставленным
				//(порядок атрибутов фиксирован Html::$attributeOrder: ...value, checked)
				$I->seeResponseContains('value="'.$type->id.'" checked');
				//сервисное переопределение сетевых параметров предзаполняет инпут
				//IP-параметров вместо дефолта типа
				$I->seeResponseContains('TCP 8140');
			},
		];

		return $scenarios;
	}
}
