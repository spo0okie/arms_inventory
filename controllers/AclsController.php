<?php

namespace app\controllers;

use app\components\Forms\ArmsForm;
use app\components\Forms\assets\ArmsFormAsset;
use app\generation\ModelFactory;
use app\helpers\ModelHelper;
use app\models\Aces;
use app\models\Acls;
use app\modules\schedules\models\Schedules;
use Yii;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;


/**
 * AclsController implements the CRUD actions for Acls model.
 */
class AclsController extends ArmsBaseController
{
	public $modelClass=Acls::class;
	
	public function disabledActions()
	{
		return ['item-by-name',];
	}
	
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			'view'=>['ace-cards'],
			//групповые операции — уровень edit
			'edit'=>['group-ace-add','group-ace-edit','group-ace-delete','group-resources','delete-group'],
			'edit-acls'=>['group-ace-add','group-ace-edit','group-ace-delete','group-resources','delete-group'],
		]);
	}
	
	/**
	 * Отображает список ACE (Access Control Entry) для заданного ACL.
	 *
	 * Рендерит представление ace-cards с моделью ACL и всеми
	 * привязанными к ней записями ACE.
	 *
	 * GET-параметры:
	 * @param int $id Идентификатор ACL-записи (acls.id)
	 *
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException если ACL с заданным id не найден
	 */
	public function actionAceCards(int $id) {
		return $this->defaultRender('ace-cards',['model'=>$this->findModel($id)]);
	}
	
	/**
	 * Тестовые данные приёмочного теста для actionAceCards.
	 *
	 * Тест пропущен (skip): для корректной проверки необходим сохранённый ACL
	 * с как минимум одним привязанным ACE. Условие выполнимо через getTestData(),
	 * если фабрика модели Acls создаёт объект с предзаполненными связанными ACE
	 * через yii2-linker-behavior. До реализации такой фабрики тест отключён.
	 *
	 * @return array сценарий skip
	 */
	public function testAceCards(): array
	{
		$testData = $this->getTestData();
		return [[
			'name'     => 'default',
			'GET'      => ['id' => $testData['full']->id],
			'response' => 200,
		]];
	}
	
	public function routeOnUpdate($model)
	{
		if (Yii::$app->request->get('accept')) return ['update','id'=>$model->id];
		return $model->schedules_id?
			['/scheduled-access/view','id'=>$model->schedules_id]:
			['view','id'=>$model->id];
	}
	
	
	/**
	 * @inheritdoc
	 */
    public function routeOnDelete($model)
    {
    	/** @var Acls $model */
    	$schedules_id=$model->schedules_id;
        return $schedules_id?
			['/scheduled-access/view','id'=>$schedules_id]:
			['/scheduled-access/index-acl'];
    }
	
	
	/**
	 * Одиночные ресурсные атрибуты ACL (один ресурс на ACL).
	 */
	const RESOURCE_FIELDS = ['comps_id','techs_id','ips_id','networks_id','services_id'];

	/**
	 * Соответствие групповых мультиселект-полей (массивы *_ids формы) одиночным ресурсам ACL.
	 */
	const GROUP_RESOURCE_MAP = [
		'comps_ids'    => 'comps_id',
		'techs_ids'    => 'techs_id',
		'ips_ids'      => 'ips_id',
		'networks_ids' => 'networks_id',
		'services_ids' => 'services_id',
	];

	/**
	 * Собирает из POST список ресурсов для группового создания.
	 *
	 * Основной источник — групповые мультиселекты Acls[*_ids][]; для обратной совместимости
	 * (REST / одиночные поля) читаются и Acls[*_id]. comment — одиночный текстовый ресурс «Другое».
	 *
	 * @param array $post полный массив POST-данных запроса
	 * @return array список пар [имя_атрибута_ресурса, значение]
	 */
	protected function collectResources(array $post): array
	{
		$aclPost=$post['Acls']??[];
		$resources=[];
		$seen=[];
		$add=function($field,$value) use (&$resources,&$seen) {
			if ($value===null || $value==='') return;
			$key=$field.':'.$value;
			if (isset($seen[$key])) return;
			$seen[$key]=true;
			$resources[]=[$field,$value];
		};

		//групповые массивы *_ids
		foreach (static::GROUP_RESOURCE_MAP as $plural=>$single) {
			foreach ((array)($aclPost[$plural]??[]) as $id) if ($id) $add($single,(int)$id);
		}
		//одиночные *_id (REST / обратная совместимость; форма их не шлёт)
		foreach (static::RESOURCE_FIELDS as $single) {
			foreach ((array)($aclPost[$single]??[]) as $id) if ($id) $add($single,(int)$id);
		}
		//«Другое» — одиночное текстовое описание
		if (isset($aclPost['comment']) && trim((string)$aclPost['comment'])!=='')
			$add('comment',trim($aclPost['comment']));

		return $resources;
	}

	/**
	 * Ajax-валидация модели с поддержкой группового сценария.
	 *
	 * Групповые формы (создание/ресурсы) передают ?scenario=group, чтобы валидировались
	 * массивы *_ids (а не одиночные *_id). Остальные формы валидируются как обычно.
	 *
	 * @param int|null $id
	 * @return array|null
	 * @throws \yii\web\NotFoundHttpException
	 */
	public function actionValidate($id=null)
	{
		$model=is_null($id)?new $this->modelClass():$this->findModel($id);
		$model->setScenario(
			Yii::$app->request->get('scenario')===Acls::SCENARIO_GROUP?
				Acls::SCENARIO_GROUP:
				Acls::SCENARIO_VALIDATION
		);
		if ($model->load(Yii::$app->request->post())) {
			Yii::$app->response->format=Response::FORMAT_JSON;
			return ArmsForm::validate($model);
		}
		return null;
	}

	/**
	 * Создаёт один или несколько ACL (групповой ACL) с одинаковым набором ACE.
	 *
	 * Логика: форма позволяет выбрать несколько ресурсов (возможно разных типов).
	 * На каждый выбранный ресурс создаётся отдельный ACL (один ресурс на ACL),
	 * а единственный ACE из формы повторяется для каждого созданного ACL.
	 * Если выбран один ресурс — поведение совместимо с прежним одиночным сценарием.
	 *
	 * Все операции выполняются в одной транзакции: при ошибке сохранения любого
	 * ACL или ACE откатывается вся группа (частично созданных объектов не остаётся).
	 *
	 * POST-параметры (через load):
	 *   - Acls[comps_id|techs_id|ips_id|networks_id|services_id][] — выбранные ресурсы (мультиселект)
	 *   - Acls[comment]      — одиночный ресурс «Другое»
	 *   - Acls[schedules_id] — расписание доступа (общее для группы)
	 *   - Acls[notepad]      — записная книжка (копируется в каждый ACL)
	 *   - Aces[*]            — общий набор ACE (опционально), повторяется для каждого ACL
	 *
	 * GET-параметры (предзаполнение формы):
	 *   - Acls[*] / Aces[*]  — предзаполнение полей
	 *   - accept             — если передан, после сохранения перенаправляет на update
	 *
	 * При успехе перенаправляет на страницу расписания (schedules) или view.
	 *
	 * @return mixed
	 */
	public function actionCreate()
	{
		$this->view->registerAssetBundle(ArmsFormAsset::class);
		/** @var Acls $model */
		$model = new $this->modelClass();
		$model->scenario = Acls::SCENARIO_GROUP; //ресурсы — мультиселекты *_ids
		$ace = new Aces();
		$post = Yii::$app->request->post();

		//режим «новый временный доступ» (issue #214): в этой же форме создаётся и расписание,
		//к которому привязываются создаваемые ACL — чтобы не оставлять расписание без ACL
		$newSchedule=(bool)(Yii::$app->request->get('newSchedule')??Yii::$app->request->post('newSchedule'));
		$schedule=$newSchedule?new Schedules():null;

		if ($model->load($post)) {
			//носитель ACE для повторного отображения формы (с введёнными данными/ошибками)
			$ace->load($post);
			if ($schedule) $schedule->load($post);

			$resources=$this->collectResources($post);
			if (!$resources) {
				$model->addError('comps_ids','Выберите хотя бы один ресурс, к которому предоставляется доступ');
				return $this->defaultRender('create', compact('model','ace','schedule'));
			}
			if ($schedule && !$schedule->validate()) {
				return $this->defaultRender('create', compact('model','ace','schedule'));
			}

			$transaction=Yii::$app->db->beginTransaction();
			try {
				//расписание (если создаём вместе с доступом) — первым, чтобы привязать к нему ACL
				$schedules_id=$model->schedules_id;
				if ($schedule) {
					if (!$schedule->save()) {
						throw new \RuntimeException('Не удалось сохранить расписание доступа');
					}
					$schedules_id=$schedule->id;
				}

				$created=[];
				foreach ($resources as [$field,$value]) {
					$acl=new Acls();
					$acl->schedules_id=$schedules_id;
					$acl->notepad=$model->notepad;
					$acl->$field=$value;
					if (!$acl->save()) {
						$model->addErrors($acl->getErrors());
						throw new \RuntimeException('Не удалось сохранить ACL');
					}

					//повторяем единственный ACE формы для каждого созданного ACL
					$rowAce=new Aces();
					if ($rowAce->load($post)) {
						$rowAce->acls_id=$acl->id;
						if (!$rowAce->save()) {
							$ace=$rowAce; //показать ошибки ACE в форме
							throw new \RuntimeException('Не удалось сохранить ACE');
						}
					}
					$created[]=$acl;
				}
				$transaction->commit();
				return $this->defaultReturn($this->routeOnUpdate($created[0]), $created);
			} catch (\Throwable $e) {
				$transaction->rollBack();
				Yii::$app->session->setFlash('error',$e->getMessage());
				return $this->defaultRender('create', compact('model','ace','schedule'));
			}
		}

		//первичное отображение формы (GET-предзаполнение)
		$model->load(Yii::$app->request->get());
		$ace->load(Yii::$app->request->get());
		if ($schedule) $schedule->load(Yii::$app->request->get());
		return $this->defaultRender('create', compact('model','ace','schedule'));
	}

	/**
	 * Тест для {@see actionCreate()}: открытие формы, одиночное и групповое создание.
	 *
	 * Сценарии:
	 *  1. `'form load'`   — GET без параметров: форма создания открывается (200).
	 *  2. `'form post'`   — POST одиночной модели (обратная совместимость): 200/302.
	 *  3. `'group post'`  — POST с несколькими ресурсами в одном submit (групповое
	 *     создание): на каждый ресурс создаётся отдельный ACL. Ожидается 200/302.
	 *
	 * Для группового сценария переиспользуется ресурс, который ModelFactory уже
	 * привязал к `create`-модели: первый заполненный одиночный ресурс дублируется
	 * в соответствующий мультиселект (создаётся два ACL); если объектного ресурса
	 * нет — используется поле comment.
	 *
	 * @return array тестовые сценарии для acceptance-тестирования
	 */
	public function testCreate(): array
	{
		$testData=$this->getTestData();
		/** @var Acls $create */
		$create=$testData['create'];

		$groupAcls=[
			'schedules_id'=>$create->schedules_id,
			'notepad'=>$create->notepad,
		];
		$picked=false;
		foreach (static::GROUP_RESOURCE_MAP as $plural=>$single) {
			if (!empty($create->$single)) {
				//дублируем один и тот же ресурс в мультиселект — создаётся два ACL за один submit
				$groupAcls[$plural]=[$create->$single,$create->$single];
				$picked=true;
				break;
			}
		}
		if (!$picked) $groupAcls['comment']=$create->comment ?: 'group test';

		return [
			['name' => 'form load'],
			[
				'name' => 'form post',
				'POST' => ModelHelper::fillForm($create),
				'response' => [200,302],
			],
			[
				'name' => 'group post',
				'POST' => ['Acls' => $groupAcls],
				'response' => [200,302],
			],
			//новый временный доступ: форма с полями расписания (issue #214)
			[
				'name' => 'new schedule form',
				'GET'  => ['newSchedule' => 1],
				'response' => 200,
			],
			//создание расписания + ACL + ACE одним submit
			[
				'name' => 'new schedule post',
				'GET'  => ['newSchedule' => 1],
				'POST' => [
					'newSchedule' => 1,
					'Schedules' => ['name' => 'тест-доступ #214'],
					'Acls'      => array_diff_key($groupAcls,['schedules_id'=>1]),
					'Aces'      => ['comment' => 'доступ #214'],
				],
				'response' => [200,302],
				'assert' => static function (\AcceptanceTester $I) {
					//расписание создано и к нему привязан хотя бы один ACL
					$sch=\app\modules\schedules\models\Schedules::find()->where(['name'=>'тест-доступ #214'])->one();
					\PHPUnit\Framework\Assert::assertNotNull($sch,'Расписание доступа должно создаться');
					\PHPUnit\Framework\Assert::assertNotEmpty(
						Acls::find()->where(['schedules_id'=>$sch->id])->count(),
						'К новому расписанию должен быть привязан ACL'
					);
				},
			],
		];
	}

	/**
	 * Маршрут возврата после групповой операции (страница расписания или просмотр ACL).
	 */
	protected function groupBackRoute(Acls $anchor): array
	{
		return $anchor->schedules_id?
			['/scheduled-access/view','id'=>$anchor->schedules_id]:
			['/acls/view','id'=>$anchor->id];
	}

	/**
	 * Добавляет одинаковый ACE ко всем ACL группы.
	 *
	 * Группа — ACL того же расписания с таким же набором ACE, что у эталона $id.
	 * На каждого члена создаётся свежий ACE из данных формы. Атомарно.
	 *
	 * @param int $id id эталонного ACL группы
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException
	 */
	public function actionGroupAceAdd(int $id)
	{
		$this->view->registerAssetBundle(ArmsFormAsset::class);
		/** @var Acls $anchor */
		$anchor=$this->findModel($id);
		$members=$anchor->groupMembers();
		$title='Добавить ACE всей группе';

		$ace=new Aces();
		$ace->acls_id=$anchor->id;
		$post=Yii::$app->request->post();

		if (Yii::$app->request->isPost && $ace->load($post)) {
			$ace->acls_id=$anchor->id; //для валидации
			if ($ace->validate()) {
				$transaction=Yii::$app->db->beginTransaction();
				try {
					foreach ($members as $member) {
						$newAce=new Aces();
						$newAce->load($post);
						$newAce->acls_id=$member->id;
						if (!$newAce->save()) {
							$ace->addErrors($newAce->getErrors());
							throw new \RuntimeException('Не удалось сохранить ACE');
						}
					}
					$transaction->commit();
					return $this->defaultReturn($this->groupBackRoute($anchor), $members);
				} catch (\Throwable $e) {
					$transaction->rollBack();
					Yii::$app->session->setFlash('error',$e->getMessage());
				}
			}
		}
		return $this->defaultRender('group-ace-form', compact('anchor','ace','members','title'));
	}

	/**
	 * Изменяет один ACE сразу во всех ACL группы.
	 *
	 * В каждом члене группы находится «ACE-близнец» (с такой же сигнатурой, как у $ace до правки)
	 * и обновляется данными формы. Атомарно.
	 *
	 * @param int $id  id эталонного ACL группы
	 * @param int $ace id редактируемого ACE (должен принадлежать эталону)
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException
	 */
	public function actionGroupAceEdit(int $id, int $ace)
	{
		$this->view->registerAssetBundle(ArmsFormAsset::class);
		/** @var Acls $anchor */
		$anchor=$this->findModel($id);
		$src=Aces::findOne($ace);
		if (!$src || $src->acls_id!=$anchor->id) {
			throw new \yii\web\NotFoundHttpException('ACE не найден в эталонном ACL группы');
		}
		$members=$anchor->groupMembers();
		$oldSig=$src->aceSignature();
		$title='Изменить ACE во всей группе';

		if (Yii::$app->request->isPost) {
			$post=Yii::$app->request->post();
			$form=new Aces();
			$form->load($post);
			$form->acls_id=$anchor->id;
			if ($form->validate()) {
				$transaction=Yii::$app->db->beginTransaction();
				try {
					foreach ($members as $member) {
						$twin=$member->findAceBySignature($oldSig);
						if (!$twin) continue;
						$twin->load($post);
						$twin->acls_id=$member->id; //не переносим ACE в другой ACL
						if (!$twin->save()) {
							$form->addErrors($twin->getErrors());
							throw new \RuntimeException('Не удалось сохранить ACE');
						}
					}
					$transaction->commit();
					return $this->defaultReturn($this->groupBackRoute($anchor), $members);
				} catch (\Throwable $e) {
					$transaction->rollBack();
					Yii::$app->session->setFlash('error',$e->getMessage());
				}
			}
			return $this->defaultRender('group-ace-form', ['anchor'=>$anchor,'ace'=>$form,'members'=>$members,'title'=>$title]);
		}

		//GET — форма, предзаполненная редактируемым ACE
		return $this->defaultRender('group-ace-form', ['anchor'=>$anchor,'ace'=>$src,'members'=>$members,'title'=>$title]);
	}

	/**
	 * Удаляет один ACE сразу во всех ACL группы (по «ACE-близнецу»).
	 *
	 * Только POST (с подтверждением). Атомарно.
	 *
	 * @param int $id  id эталонного ACL группы
	 * @param int $ace id удаляемого ACE (должен принадлежать эталону)
	 * @return \yii\web\Response
	 * @throws \yii\web\NotFoundHttpException
	 * @throws MethodNotAllowedHttpException
	 */
	public function actionGroupAceDelete(int $id, int $ace)
	{
		if (!Yii::$app->request->isPost) {
			throw new MethodNotAllowedHttpException('Удаление ACE возможно только методом POST');
		}
		/** @var Acls $anchor */
		$anchor=$this->findModel($id);
		$src=Aces::findOne($ace);
		if (!$src || $src->acls_id!=$anchor->id) {
			throw new \yii\web\NotFoundHttpException('ACE не найден в эталонном ACL группы');
		}
		$members=$anchor->groupMembers();
		$oldSig=$src->aceSignature();

		$transaction=Yii::$app->db->beginTransaction();
		try {
			foreach ($members as $member) {
				$twin=$member->findAceBySignature($oldSig);
				if ($twin) $twin->delete();
			}
			$transaction->commit();
		} catch (\Throwable $e) {
			$transaction->rollBack();
			Yii::$app->session->setFlash('error','Не удалось удалить ACE: '.$e->getMessage());
		}
		return $this->redirect($this->groupBackRoute($anchor));
	}

	/**
	 * Тест {@see actionGroupAceAdd()}: добавленный ACE появляется у всех членов группы.
	 */
	public function testGroupAceAdd(): array
	{
		$fixture=$this->buildGroupFixture();
		if (!$fixture) return static::skipScenario('default','не удалось построить фикстуру группы');
		[$anchorId,$memberIds,$controlId]=$fixture;

		return [
			['name'=>'form open','GET'=>['id'=>$anchorId],'response'=>200],
			[
				'name'=>'apply',
				'GET'=>['id'=>$anchorId],
				'POST'=>['Aces'=>['comment'=>'добавленный доступ']],
				'response'=>[200,302],
				'assert'=>static function (\AcceptanceTester $I) use ($memberIds,$controlId) {
					foreach ($memberIds as $mid) {
						$comments=Aces::find()->select('comment')->where(['acls_id'=>$mid])->column();
						\PHPUnit\Framework\Assert::assertContains('исходный доступ',$comments,"ACL $mid сохраняет исходный ACE");
						\PHPUnit\Framework\Assert::assertContains('добавленный доступ',$comments,"ACL $mid получает добавленный ACE");
					}
					$control=Aces::find()->select('comment')->where(['acls_id'=>$controlId])->column();
					\PHPUnit\Framework\Assert::assertNotContains('добавленный доступ',$control,'Контрольный ACL не должен меняться');
				},
			],
		];
	}

	/**
	 * Тест {@see actionGroupAceEdit()}: правка ACE применяется ко всем членам группы.
	 */
	public function testGroupAceEdit(): array
	{
		$fixture=$this->buildGroupFixture();
		if (!$fixture) return static::skipScenario('default','не удалось построить фикстуру группы');
		[$anchorId,$memberIds,$controlId]=$fixture;
		$anchorAce=Aces::find()->where(['acls_id'=>$anchorId])->one();
		if (!$anchorAce) return static::skipScenario('default','у эталона нет ACE');

		return [
			['name'=>'form open','GET'=>['id'=>$anchorId,'ace'=>$anchorAce->id],'response'=>200],
			[
				'name'=>'apply',
				'GET'=>['id'=>$anchorId,'ace'=>$anchorAce->id],
				'POST'=>['Aces'=>['comment'=>'правленый доступ']],
				'response'=>[200,302],
				'assert'=>static function (\AcceptanceTester $I) use ($memberIds,$controlId) {
					foreach ($memberIds as $mid) {
						$aces=Aces::find()->where(['acls_id'=>$mid])->all();
						\PHPUnit\Framework\Assert::assertCount(1,$aces,"ACL $mid сохраняет один ACE");
						\PHPUnit\Framework\Assert::assertEquals('правленый доступ',$aces[0]->comment,"ACE ACL $mid обновлён");
					}
					$control=Aces::find()->where(['acls_id'=>$controlId])->all();
					\PHPUnit\Framework\Assert::assertNotEquals('правленый доступ',$control[0]->comment,'Контрольный ACL не должен меняться');
				},
			],
		];
	}

	/**
	 * Тест {@see actionGroupAceDelete()}: удаление ACE убирает его у всех членов группы.
	 */
	public function testGroupAceDelete(): array
	{
		$fixture=$this->buildGroupFixture();
		if (!$fixture) return static::skipScenario('default','не удалось построить фикстуру группы');
		[$anchorId,$memberIds,$controlId]=$fixture;
		$anchorAce=Aces::find()->where(['acls_id'=>$anchorId])->one();
		if (!$anchorAce) return static::skipScenario('default','у эталона нет ACE');

		return [[
			'name'=>'default',
			'GET'=>['id'=>$anchorId,'ace'=>$anchorAce->id],
			'POST'=>[],
			'response'=>302,
			'assert'=>static function (\AcceptanceTester $I) use ($memberIds,$controlId) {
				foreach ($memberIds as $mid) {
					\PHPUnit\Framework\Assert::assertCount(0,Aces::find()->where(['acls_id'=>$mid])->all(),"ACE удалён у члена $mid");
				}
				\PHPUnit\Framework\Assert::assertCount(1,Aces::find()->where(['acls_id'=>$controlId])->all(),'Контрольный ACL не должен меняться');
			},
		]];
	}

	/**
	 * Строит расписание: группа из двух ACL с одинаковым ACE + контрольный ACL с другим ACE.
	 *
	 * @return array{0:int,1:int[],2:int}|null [anchorId, [memberIds], controlId] либо null
	 */
	protected function buildGroupFixture(): ?array
	{
		try {
			$schedule=ModelFactory::create(Schedules::class,['empty'=>true]);
			if (!$schedule) return null;

			$memberIds=[];
			foreach (['ресурс-1','ресурс-2'] as $resource) {
				$acl=new Acls();
				$acl->schedules_id=$schedule->id;
				$acl->comment=$resource;
				if (!$acl->save()) return null;
				$ace=new Aces();
				$ace->acls_id=$acl->id;
				$ace->comment='исходный доступ';
				if (!$ace->save()) return null;
				$memberIds[]=$acl->id;
			}

			//контрольный ACL того же расписания, но с другим ACE → вне группы
			$control=new Acls();
			$control->schedules_id=$schedule->id;
			$control->comment='ресурс-контроль';
			if (!$control->save()) return null;
			$controlAce=new Aces();
			$controlAce->acls_id=$control->id;
			$controlAce->comment='другой доступ';
			if (!$controlAce->save()) return null;

			return [$memberIds[0],$memberIds,$control->id];
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * Определяет ресурс ACL как пару [имя_поля, значение]: объектный ресурс (comps_id/...)
	 * либо текстовый comment. Пустой ключ — если ресурс не задан.
	 *
	 * @param Acls $acl
	 * @return array{0:string,1:mixed}
	 */
	protected function aclResourceKey(Acls $acl): array
	{
		foreach (static::RESOURCE_FIELDS as $f) if (!empty($acl->$f)) return [$f,(int)$acl->$f];
		if (trim((string)$acl->comment)!=='') return ['comment',$acl->comment];
		return ['',null];
	}

	/**
	 * Редактирование группы ACL — по UX аналогично одиночному редактированию ACL, но с
	 * множественными инпутами: слева общий набор ACE группы (правка/удаление/добавление —
	 * групповые операции), справа мультиселекты ресурсов + общая записная книжка.
	 *
	 * Набор ресурсов: разница «желаемого» (форма) и текущего — недостающие добавляются
	 * (новый ACL с КОПИЕЙ общего набора ACE группы), исчезнувшие удаляются (1 ACL = 1 ресурс).
	 * Записная книжка применяется ко всем ACL группы. Набор ACE формой не меняется (для этого —
	 * групповые ACE-операции). Всё атомарно; ACL вне группы не затрагиваются.
	 *
	 * POST-параметры:
	 *   - Acls[comps_ids|techs_ids|ips_ids|networks_ids|services_ids][] — объектные ресурсы (мультиселект)
	 *   - Acls[comment]  — текстовые ресурсы «Другое» через запятую
	 *   - Acls[notepad]  — записная книжка (для всех ACL группы)
	 *
	 * @param int $id id эталонного ACL группы
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException
	 */
	public function actionGroupResources(int $id)
	{
		$this->view->registerAssetBundle(ArmsFormAsset::class);
		/** @var Acls $anchor */
		$anchor=$this->findModel($id);
		$members=$anchor->groupMembers();
		$backRoute=$this->groupBackRoute($anchor);

		if (Yii::$app->request->isPost) {
			$aclPost=Yii::$app->request->post('Acls',[]);
			$notepad=$aclPost['notepad']??null; //записная книжка — общая для всей группы

			//желаемый набор ресурсов из формы (мультиселекты *_ids + текстовые comment через запятую)
			$desired=[];
			foreach (static::GROUP_RESOURCE_MAP as $plural=>$single) {
				foreach ((array)($aclPost[$plural]??[]) as $v) {
					$v=(int)$v; if (!$v) continue;
					$desired[$single.':'.$v]=[$single,$v];
				}
			}
			foreach (explode(',',(string)($aclPost['comment']??'')) as $c) {
				$c=trim($c); if ($c==='') continue;
				$desired['comment:'.$c]=['comment',$c];
			}

			if (!$desired) {
				//пустой набор ресурсов не сохраняем (иначе удалили бы всю группу)
				Yii::$app->session->setFlash('error','Выберите хотя бы один ресурс группы');
			} else {
				//текущий набор ресурсов группы (ключ ресурса => член группы)
				$current=[];
				foreach ($members as $m) {
					[$f,$v]=$this->aclResourceKey($m);
					if ($f==='') continue;
					$current[$f.':'.$v]=$m;
				}

				$transaction=Yii::$app->db->beginTransaction();
				try {
					//1) добавляем недостающие ресурсы (копия ACE эталона, пока он существует)
					foreach ($desired as $key=>[$field,$value]) {
						if (isset($current[$key])) continue;
						$acl=new Acls();
						$acl->schedules_id=$anchor->schedules_id;
						$acl->notepad=$notepad;
						$acl->$field=$value;
						if (!$acl->save()) {
							$anchor->addErrors($acl->getErrors());
							throw new \RuntimeException('Не удалось создать ACL для ресурса');
						}
						foreach ($anchor->aces as $srcAce) {
							$newAce=new Aces();
							$srcAce->copyContentTo($newAce);
							$newAce->acls_id=$acl->id;
							if (!$newAce->save()) {
								throw new \RuntimeException('Не удалось скопировать ACE в новый ресурс');
							}
						}
					}
					//2) существующие: удаляем исчезнувшие, у остальных обновляем общую записную книжку
					foreach ($current as $key=>$member) {
						if (!isset($desired[$key])) { $member->delete(); continue; }
						if ((string)$member->notepad!==(string)$notepad) {
							$member->notepad=$notepad;
							if (!$member->save()) {
								$anchor->addErrors($member->getErrors());
								throw new \RuntimeException('Не удалось сохранить записную книжку');
							}
						}
					}
					$transaction->commit();
					return $this->defaultReturn($backRoute, $members);
				} catch (\Throwable $e) {
					$transaction->rollBack();
					Yii::$app->session->setFlash('error',$e->getMessage());
				}
			}
		}

		//GET: карьер, предзаполненный текущими ресурсами и записной книжкой группы
		$model=new Acls();
		$model->scenario=Acls::SCENARIO_GROUP;
		$model->schedules_id=$anchor->schedules_id;
		$model->notepad=$anchor->notepad;
		$singleToPlural=array_flip(static::GROUP_RESOURCE_MAP);
		$byPlural=array_fill_keys(array_keys(static::GROUP_RESOURCE_MAP),[]);
		$comments=[];
		foreach ($members as $m) {
			[$f,$v]=$this->aclResourceKey($m);
			if ($f==='comment') $comments[]=$v;
			elseif (isset($singleToPlural[$f])) $byPlural[$singleToPlural[$f]][]=$v;
		}
		foreach ($byPlural as $plural=>$vals) $model->$plural=$vals;
		$model->comment=implode(', ',$comments);
		return $this->defaultRender('group-resources', compact('anchor','members','model'));
	}

	/**
	 * Тест для {@see actionGroupResources()}: добавление и удаление ресурсов группы.
	 *
	 * Строит группу из двух ACL (одинаковый ACE) + контрольный ACL вне группы. POST добавляет
	 * новый ресурс и удаляет один член группы. Проверяет (по БД): удалённый член исчез, эталон
	 * сохранён, появился новый ACL с тем же набором ACE (тот же комментарий ACE), контрольный
	 * ACL не тронут.
	 *
	 * @return array
	 */
	public function testGroupResources(): array
	{
		$fixture=$this->buildGroupFixture();
		if (!$fixture) {
			return static::skipScenario('default','не удалось построить фикстуру группы');
		}
		[$anchorId,$memberIds,$controlId]=$fixture;

		return [
			[
				'name' => 'form open',
				'GET'  => ['id' => $anchorId],
				'response' => 200,
			],
			[
				'name' => 'apply',
				'GET'  => ['id' => $anchorId],
				//оставляем «ресурс-1», убираем «ресурс-2», добавляем «новый ресурс», меняем записную книжку
				'POST' => ['Acls' => ['comment' => 'ресурс-1, новый ресурс', 'notepad' => 'общая книжка']],
				'response' => [200,302],
				'assert' => static function (\AcceptanceTester $I) use ($anchorId,$memberIds,$controlId) {
					$anchor=Acls::findOne($anchorId);
					\PHPUnit\Framework\Assert::assertNotNull($anchor,'Эталонный ACL должен сохраниться');
					//записная книжка применилась ко всей группе (к оставшемуся члену)
					\PHPUnit\Framework\Assert::assertEquals('общая книжка',$anchor->notepad,'Записная книжка обновляется у всех ACL группы');
					//удалённый ресурс исчез
					\PHPUnit\Framework\Assert::assertNull(Acls::findOne($memberIds[1]),'Удалённый ресурс должен исчезнуть');
					//появился новый ACL с тем же набором ACE (тот же комментарий ACE) и общей книжкой
					$new=Acls::find()->where(['schedules_id'=>$anchor->schedules_id,'comment'=>'новый ресурс'])->one();
					\PHPUnit\Framework\Assert::assertNotNull($new,'Должен появиться новый ACL для добавленного ресурса');
					\PHPUnit\Framework\Assert::assertEquals('общая книжка',$new->notepad,'Новый ACL получает общую записную книжку');
					$newAces=Aces::find()->where(['acls_id'=>$new->id])->all();
					\PHPUnit\Framework\Assert::assertCount(1,$newAces,'У нового ACL должен быть скопирован набор ACE');
					\PHPUnit\Framework\Assert::assertEquals('исходный доступ',$newAces[0]->comment,'Новый ACL получает тот же ACE, что и группа');
					//контрольный ACL вне группы не тронут
					\PHPUnit\Framework\Assert::assertNotNull(Acls::findOne($controlId),'Контрольный ACL не должен затрагиваться');
				},
			],
		];
	}

	/**
	 * Удаляет всю группу ACL: все ACL того же расписания с таким же набором ACE, что у $id.
	 *
	 * Только POST (вызывается с подтверждением). Удаление атомарно; расписание доступа
	 * не удаляется (только ACL-члены группы; каскадное удаление ACE — в {@see Acls::beforeDelete()}).
	 * ACL вне группы (с другим набором ACE) не затрагиваются.
	 *
	 * @param int $id id эталонного ACL группы
	 * @return \yii\web\Response
	 * @throws \yii\web\NotFoundHttpException
	 * @throws MethodNotAllowedHttpException
	 */
	public function actionDeleteGroup(int $id)
	{
		if (!Yii::$app->request->isPost) {
			throw new MethodNotAllowedHttpException('Удаление группы возможно только методом POST');
		}
		/** @var Acls $anchor */
		$anchor=$this->findModel($id);
		$schedules_id=$anchor->schedules_id;
		$members=$anchor->groupMembers();

		$transaction=Yii::$app->db->beginTransaction();
		try {
			foreach ($members as $member) $member->delete();
			$transaction->commit();
		} catch (\Throwable $e) {
			$transaction->rollBack();
			Yii::$app->session->setFlash('error','Не удалось удалить группу: '.$e->getMessage());
		}

		return $this->redirect($schedules_id?
			['/scheduled-access/view','id'=>$schedules_id]:
			['/acls/index']);
	}

	/**
	 * Тест для {@see actionDeleteGroup()}: удаление всей группы не трогает ACL вне группы.
	 *
	 * @return array
	 */
	public function testDeleteGroup(): array
	{
		$fixture=$this->buildGroupFixture();
		if (!$fixture) {
			return static::skipScenario('default','не удалось построить фикстуру группы');
		}
		[$anchorId,$memberIds,$controlId]=$fixture;

		return [[
			'name' => 'default',
			'GET'  => ['id' => $anchorId],
			'POST' => [],
			'response' => 302,
			'assert' => static function (\AcceptanceTester $I) use ($memberIds,$controlId) {
				//все члены группы удалены
				foreach ($memberIds as $mid) {
					\PHPUnit\Framework\Assert::assertNull(Acls::findOne($mid),"ACL $mid должен быть удалён вместе с группой");
				}
				//контрольный ACL вне группы сохранился
				\PHPUnit\Framework\Assert::assertNotNull(Acls::findOne($controlId),"Контрольный ACL $controlId не должен удаляться");
			},
		]];
	}

}
